/*
 * Smart Gate ESP32 + dual RC522 RFID bridge.
 * BISM4RCK-KUN3H0 2026
 * VSPI: SCK 18, MOSI 23, MISO 19, RST 22.
 * Entry: SS 5, green 25, red 26.
 * Exit: SS 16, green 32, red 33.
 * System: green 13, red 14. Gate relay: 27.
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <SPI.h>
#include <MFRC522.h>

const char* WIFI_SSID = "TP_link1";
const char* WIFI_PASSWORD = "Benq2877";
const char* SMART_GATE_BASE_URL = "https://docile-darkroom-sinless.ngrok-free.dev/smart-gate/";
const char* SMART_GATE_DEVICE_KEY = "2jGpAbQGBVW9qJU89UQjDxNAjNMtj2q-JsuvL9dE8Ig";
const char* SMART_GATE_DEVICE_ID = "180503";

constexpr uint8_t SPI_SCK_PIN = 18;
constexpr uint8_t SPI_MOSI_PIN = 23;
constexpr uint8_t SPI_MISO_PIN = 19;
constexpr uint8_t RC522_RST_PIN = 22;

constexpr uint8_t ENTRY_SS_PIN = 5;
constexpr uint8_t ENTRY_GREEN_LED = 25;
constexpr uint8_t ENTRY_RED_LED = 26;

constexpr uint8_t EXIT_SS_PIN = 16;
constexpr uint8_t EXIT_GREEN_LED = 32;
constexpr uint8_t EXIT_RED_LED = 33;

constexpr uint8_t SYSTEM_GREEN_LED = 13;
constexpr uint8_t SYSTEM_RED_LED = 14;
constexpr uint8_t GATE_RELAY_PIN = 27;

constexpr uint32_t GATE_OPEN_MS = 2500;
constexpr uint16_t SERVER_POLL_MS = 650;
constexpr uint16_t GATE_COMMAND_POLL_MS = 350;
constexpr uint16_t HTTP_TIMEOUT_MS = 3500;
constexpr uint16_t HTTP_CONNECT_TIMEOUT_MS = 2000;
constexpr uint16_t CARD_RESULT_LED_MS = 1500;
constexpr uint16_t SCAN_COOLDOWN_MS = 650;
constexpr uint8_t PROFILE_BLOCK = 4;
constexpr uint8_t PROFILE_BLOCK_COUNT = 3;
constexpr MFRC522::PCD_RxGain RFID_RX_GAIN = MFRC522::RxGain_max;

MFRC522 entryReader(ENTRY_SS_PIN, RC522_RST_PIN);
MFRC522 exitReader(EXIT_SS_PIN, RC522_RST_PIN);
MFRC522::MIFARE_Key keyA;

unsigned long lastServerPoll = 0;
unsigned long lastGateCommandPoll = 0;
unsigned long lastWiFiRetry = 0;
unsigned long gateRelayUntil = 0;
unsigned long systemErrorUntil = 0;
unsigned long systemErrorToggleAt = 0;
unsigned long entryLedUntil = 0;
unsigned long exitLedUntil = 0;
unsigned long entryLastScanAt = 0;
unsigned long exitLastScanAt = 0;
String blockedBurnSession = "";
WiFiClientSecure secureClient;

struct ReaderConfig {
  const char* name;
  MFRC522* reader;
  uint8_t greenLed;
  uint8_t redLed;
};

const ReaderConfig ENTRY_READER = {"entry", &entryReader, ENTRY_GREEN_LED, ENTRY_RED_LED};
const ReaderConfig EXIT_READER = {"exit", &exitReader, EXIT_GREEN_LED, EXIT_RED_LED};

String uidString(MFRC522& reader);
bool waitForCard(const ReaderConfig& readerConfig, String& uid, unsigned long windowMs);
bool waitForCardRemoval(const ReaderConfig& readerConfig, unsigned long windowMs);
bool authenticateBlock(MFRC522& reader);
bool authenticateBlockWithKey(MFRC522& reader, byte keyType, MFRC522::MIFARE_Key& key) {
  MFRC522::StatusCode status = reader.PCD_Authenticate(
    keyType,
    PROFILE_BLOCK,
    &key,
    &reader.uid
  );

  if (status == MFRC522::STATUS_OK) {
    return true;
  }

  Serial.printf(
    "RFID authentication failed with %s: %s\n",
    keyType == MFRC522::PICC_CMD_MF_AUTH_KEY_A ? "Key A" : "Key B",
    reader.GetStatusCodeName(status)
  );
  reader.PCD_StopCrypto1();
  return false;
}

bool authenticateBlock(MFRC522& reader) {
  // Most MIFARE Classic cards use the factory key on Key A. Some cards
  // use the same factory key on Key B, so accept either configuration.
  if (authenticateBlockWithKey(reader, MFRC522::PICC_CMD_MF_AUTH_KEY_A, keyA)) {
    return true;
  }

  return authenticateBlockWithKey(reader, MFRC522::PICC_CMD_MF_AUTH_KEY_B, keyA);
}

bool writeProfile(MFRC522& reader, const String& code) {
  // The server stores the authoritative credential against the UID. The card
  // stores a local copy only, so keep enough space for the complete credential
  // instead of truncating newer adm-/grd-/res- naming formats to 12 characters.
  const uint16_t maxProfileLength = PROFILE_BLOCK_COUNT * 16 - 4;
  if (code.length() > maxProfileLength) {
    Serial.printf("ERROR! RFID profile is too long (%u/%u bytes).\n",
                  code.length(), maxProfileLength);
    return false;
  }

  if (!authenticateBlock(reader)) {
    return false;
  }

  byte data[PROFILE_BLOCK_COUNT][16] = {};
  data[0][0] = 'S';
  data[0][1] = 'G';
  data[0][2] = '4';
  data[0][3] = 'R';

  for (uint16_t i = 0; i < code.length(); i++) {
    const uint16_t position = i + 4;
    data[position / 16][position % 16] = static_cast<byte>(code[i]);
  }

  for (uint8_t blockOffset = 0; blockOffset < PROFILE_BLOCK_COUNT; blockOffset++) {
    const uint8_t block = PROFILE_BLOCK + blockOffset;
    MFRC522::StatusCode status = reader.MIFARE_Write(block, data[blockOffset], 16);
    if (status != MFRC522::STATUS_OK) {
      Serial.printf("ERROR! RFID write failed on block %u: %s\n",
                    block,
                    reader.GetStatusCodeName(status));
      reader.PCD_StopCrypto1();
      return false;
    }
  }

  reader.PCD_StopCrypto1();
  return true;
}

bool clearProfile(MFRC522& reader) {
  if (!authenticateBlock(reader)) {
    return false;
  }

  byte data[16] = {0};
  for (uint8_t blockOffset = 0; blockOffset < PROFILE_BLOCK_COUNT; blockOffset++) {
    const uint8_t block = PROFILE_BLOCK + blockOffset;
    MFRC522::StatusCode status = reader.MIFARE_Write(block, data, 16);
    if (status != MFRC522::STATUS_OK) {
      Serial.printf("ERROR! RFID clear failed on block %u: %s\n",
                    block,
                    reader.GetStatusCodeName(status));
      reader.PCD_StopCrypto1();
      return false;
    }
  }

  reader.PCD_StopCrypto1();
  return true;
}

bool apiGet(const String& path, String& response, int& statusCode);
bool apiPost(const String& path, const String& body, String& response, int& statusCode);
bool reportBurnFailure(const String& session, const String& uid, const String& reason);
String urlEncode(const String& input);
void openGate();
void haltCard(MFRC522& reader);
void setSystemConnected(bool connected);
void flashSystemError();
void showReaderResult(const ReaderConfig& readerConfig, bool approved);
void processBurn(const String& session, const String& code);
void processContinuousCard(const ReaderConfig& readerConfig);
void processGateCommand();
void serviceOutputs();
void scanReaderIfPresent(const ReaderConfig& readerConfig, unsigned long& lastScanAt);
void initializeReader(MFRC522& reader);

void processContinuousCard(const ReaderConfig& readerConfig) {
  unsigned long& lastScanAt = readerConfig.reader == &entryReader ? entryLastScanAt : exitLastScanAt;
  scanReaderIfPresent(readerConfig, lastScanAt);
}

void scanReaderIfPresent(const ReaderConfig& readerConfig, unsigned long& lastScanAt) {
  if (millis() - lastScanAt < SCAN_COOLDOWN_MS) {
    return;
  }

  MFRC522& reader = *readerConfig.reader;
  if (!reader.PICC_IsNewCardPresent()) {
    return;
  }

  delay(25);
  if (!reader.PICC_ReadCardSerial()) {
    return;
  }

  lastScanAt = millis();
  String uid = uidString(reader);
  Serial.printf("%s RFID DETECTED: %s\n", readerConfig.name, uid.c_str());

  String response;
  int statusCode = 0;
  String body = "session_id=continuous&rfid_uid=" + urlEncode(uid) + "&reader=" + urlEncode(readerConfig.name);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.printf("ERROR! %s RFID validation HTTP %d\n", readerConfig.name, statusCode);
    showReaderResult(readerConfig, false);
    flashSystemError();
    haltCard(reader);
    return;
  }

  bool approved = response.indexOf("\"gate_opened\":true") >= 0;
  bool pending = response.indexOf("\"gate_status\":\"pending\"") >= 0;

  if (approved) {
    Serial.printf("%s: GATE OPENED\n", readerConfig.name);
    showReaderResult(readerConfig, true);
    openGate();
  } else if (pending) {
    Serial.printf("%s: REQUEST STILL PENDING\n", readerConfig.name);
    showReaderResult(readerConfig, false);
  } else {
    Serial.printf("%s: DENIED\n", readerConfig.name);
    showReaderResult(readerConfig, false);
  }

  haltCard(reader);
}

void setup() {
  Serial.begin(115200);
  delay(500);

  pinMode(GATE_RELAY_PIN, OUTPUT);
  digitalWrite(GATE_RELAY_PIN, LOW);

  pinMode(ENTRY_GREEN_LED, OUTPUT);
  pinMode(ENTRY_RED_LED, OUTPUT);
  pinMode(EXIT_GREEN_LED, OUTPUT);
  pinMode(EXIT_RED_LED, OUTPUT);
  pinMode(SYSTEM_GREEN_LED, OUTPUT);
  pinMode(SYSTEM_RED_LED, OUTPUT);

  digitalWrite(ENTRY_GREEN_LED, LOW);
  digitalWrite(ENTRY_RED_LED, LOW);
  digitalWrite(EXIT_GREEN_LED, LOW);
  digitalWrite(EXIT_RED_LED, LOW);
  digitalWrite(SYSTEM_GREEN_LED, LOW);
  digitalWrite(SYSTEM_RED_LED, HIGH);

  SPI.begin(SPI_SCK_PIN, SPI_MISO_PIN, SPI_MOSI_PIN);
  initializeReader(entryReader);
  initializeReader(exitReader);

  for (byte i = 0; i < 6; i++) {
    keyA.keyByte[i] = 0xFF;
  }

  secureClient.setInsecure();
  secureClient.setTimeout(HTTP_TIMEOUT_MS);

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  // TP_link1 is hidden; the final argument enables hidden-network association.
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD, 0, nullptr, true);

  Serial.println("\n=== Smart Gate Dual RFID Scanner ===");
  Serial.println("Device ID: 180503");
  Serial.println("Entry RC522 SS: GPIO 5");
  Serial.println("Exit RC522 SS: GPIO 16");
  Serial.println("RFID readers: ALWAYS LISTENING");

  unsigned long started = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - started < 20000) {
    delay(300);
    Serial.print('.');
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    setSystemConnected(true);
    Serial.println("Wi-Fi connected.");
  } else {
    setSystemConnected(false);
    Serial.println("ERROR! Wi-Fi connection failed.");
  }
}

void loop() {
  serviceOutputs();

  if (WiFi.status() != WL_CONNECTED) {
    setSystemConnected(false);
    if (millis() - lastWiFiRetry >= 5000) {
      lastWiFiRetry = millis();
      WiFi.reconnect();
    }
    delay(5);
    return;
  }

  setSystemConnected(true);
  processGateCommand();

  static unsigned long lastBurnPoll = 0;
  if (millis() - lastBurnPoll >= SERVER_POLL_MS) {
    lastBurnPoll = millis();

    String response;
    int statusCode = 0;
    if (apiGet("/api/esp32/poll_rfid.php", response, statusCode)) {
      bool burnRequested = response.indexOf("\"purpose\":\"burn\"") >= 0;
      if (burnRequested) {
        int start = response.indexOf("\"session_id\":\"");
        if (start >= 0) {
          start += 14;
          int end = response.indexOf('"', start);
          if (end > start) {
            String session = response.substring(start, end);
            if (session != blockedBurnSession) {
              String credentialCode;
              int codeStart = response.indexOf("\"credential_code\":\"");
              if (codeStart >= 0) {
                codeStart += 19;
                int codeEnd = response.indexOf('"', codeStart);
                if (codeEnd > codeStart) {
                  credentialCode = response.substring(codeStart, codeEnd);
                }
              }
              processBurn(session, credentialCode);
              return;
            }
          }
        }
      }
    } else {
      flashSystemError();
    }
  }

  scanReaderIfPresent(ENTRY_READER, entryLastScanAt);
  scanReaderIfPresent(EXIT_READER, exitLastScanAt);
}

void processBurn(const String& session, const String& code) {
  Serial.println("RFID BURN: Present card on the ENTRY reader...");

  String uid;
  if (!waitForCard(ENTRY_READER, uid, 30000)) {
    Serial.println("ERROR! RFID burn timed out.");
    reportBurnFailure(session, "00000000", "RFID card was not detected before the burn session timed out.");
    showReaderResult(ENTRY_READER, false);
    blockedBurnSession = session;
    return;
  }

  if (code == "") {
    Serial.println("ERROR! No RFID profile was supplied by Smart Gate.");
    reportBurnFailure(session, uid, "No RFID profile was supplied by Smart Gate.");
    showReaderResult(ENTRY_READER, false);
    haltCard(entryReader);
    waitForCardRemoval(ENTRY_READER, 1500);
    return;
  }

  bool writeSucceeded = false;
  for (uint8_t attempt = 1; attempt <= 2; attempt++) {
    Serial.printf("RFID profile write attempt %u/2...\n", attempt);

    if (writeProfile(entryReader, code)) {
      writeSucceeded = true;
      Serial.println("RFID profile written successfully.");
      break;
    }

    Serial.printf("ERROR! RFID profile write attempt %u/2 failed.\n", attempt);
    if (attempt < 2) {
      delay(250);
    }
  }

  if (!writeSucceeded) {
    Serial.println("ERROR! RFID profile could not be written after 2 attempts.");
    Serial.println("Returning to normal gate function.");
    blockedBurnSession = session;
    reportBurnFailure(session, uid, "RFID profile write failed twice on the ESP32.");
    showReaderResult(ENTRY_READER, false);
    haltCard(entryReader);
    waitForCardRemoval(ENTRY_READER, 1500);
    return;
  }

  String response;
  int statusCode = 0;
  String body = "session_id=" + urlEncode(session) + "&rfid_uid=" + urlEncode(uid);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.printf("ERROR! Server response HTTP %d\n", statusCode);
    clearProfile(entryReader);
    reportBurnFailure(session, uid, "Smart Gate could not confirm the RFID burn.");
    showReaderResult(ENTRY_READER, false);
    haltCard(entryReader);
    waitForCardRemoval(ENTRY_READER, 1500);
    return;
  }

  if (response.indexOf("\"ok\":true") >= 0) {
    Serial.println("CARD BURNED SUCCESSFULLY: " + code);
    Serial.println("Card updated. You can return to the RFID Management page.");
    showReaderResult(ENTRY_READER, true);
  } else {
    Serial.println("ERROR! RFID profile was not accepted by Smart Gate.");
    clearProfile(entryReader);
    reportBurnFailure(session, uid, "Smart Gate rejected the RFID profile after the card write.");
    showReaderResult(ENTRY_READER, false);
  }

  haltCard(entryReader);
  waitForCardRemoval(ENTRY_READER, 1500);
}


void processGateCommand() {
  if (millis() - lastGateCommandPoll < GATE_COMMAND_POLL_MS) {
    return;
  }
  lastGateCommandPoll = millis();

  String response;
  int statusCode = 0;
  if (!apiGet("/api/esp32/gate_command.php", response, statusCode)) {
    flashSystemError();
    return;
  }

  if (response.indexOf("\"command\":null") >= 0 || response.indexOf("\"command\":false") >= 0) {
    return;
  }

  int start = response.indexOf("\"id\":");
  if (start < 0) {
    return;
  }
  start += 5;
  int end = start;
  while (end < static_cast<int>(response.length()) && isDigit(response[end])) {
    end++;
  }
  if (end <= start || response.indexOf("\"command\":\"open_gate\"") < 0) {
    return;
  }

  String commandId = response.substring(start, end);
  Serial.println("GATE COMMAND RECEIVED: " + commandId);
  openGate();

  String output;
  int outputStatus = 0;
  String body = "command_id=" + urlEncode(commandId);
  if (!apiPost("/api/esp32/complete_gate_command.php", body, output, outputStatus)) {
    flashSystemError();
    return;
  }

  Serial.println("GATE OPENED");
}

void initializeReader(MFRC522& reader) {
  reader.PCD_Init();
  delay(80);
  reader.PCD_SetAntennaGain(RFID_RX_GAIN);
  reader.PCD_AntennaOn();
}

String uidString(MFRC522& reader) {
  String output;
  for (byte i = 0; i < reader.uid.size; i++) {
    if (i) {
      output += ':';
    }
    if (reader.uid.uidByte[i] < 0x10) {
      output += '0';
    }
    output += String(reader.uid.uidByte[i], HEX);
  }
  output.toUpperCase();
  return output;
}

bool waitForCard(const ReaderConfig& readerConfig, String& uid, unsigned long windowMs) {
  unsigned long started = millis();
  while (millis() - started < windowMs) {
    if (readerConfig.reader->PICC_IsNewCardPresent() && readerConfig.reader->PICC_ReadCardSerial()) {
      uid = uidString(*readerConfig.reader);
      return true;
    }
    delay(10);
  }
  return false;
}

bool waitForCardRemoval(const ReaderConfig& readerConfig, unsigned long windowMs) {
  unsigned long started = millis();
  while (millis() - started < windowMs) {
    if (!readerConfig.reader->PICC_IsNewCardPresent()) {
      return true;
    }
    delay(10);
  }
  return true;
}









bool apiGet(const String& path, String& response, int& statusCode) {
  HTTPClient http;
  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(secureClient, base + path)) {
    statusCode = -1;
    return false;
  }

  http.setTimeout(HTTP_TIMEOUT_MS);
  http.setConnectTimeout(HTTP_CONNECT_TIMEOUT_MS);
  http.setReuse(true);
  http.addHeader("X-Smart-Gate-Key", SMART_GATE_DEVICE_KEY);
  http.addHeader("X-Smart-Gate-Device", SMART_GATE_DEVICE_ID);

  statusCode = http.GET();
  response = http.getString();
  http.end();
  return statusCode >= 200 && statusCode < 300;
}

bool reportBurnFailure(const String& session, const String& uid, const String& reason) {
  String response;
  int statusCode = 0;
  String body = "session_id=" + urlEncode(session) +
                "&rfid_uid=" + urlEncode(uid) +
                "&burn_failed=1&failure_reason=" + urlEncode(reason);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.printf("ERROR! Could not report RFID burn failure to Smart Gate (HTTP %d).\n", statusCode);
    return false;
  }

  Serial.println("RFID burn session closed. Returning to gate function.");
  return true;
}

bool apiPost(const String& path, const String& body, String& response, int& statusCode) {
  HTTPClient http;
  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(secureClient, base + path)) {
    statusCode = -1;
    return false;
  }

  http.setTimeout(HTTP_TIMEOUT_MS);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("X-Smart-Gate-Key", SMART_GATE_DEVICE_KEY);
  http.addHeader("X-Smart-Gate-Device", SMART_GATE_DEVICE_ID);

  statusCode = http.POST(body);
  response = http.getString();
  http.end();
  return statusCode >= 200 && statusCode < 300;
}

String urlEncode(const String& input) {
  const char* hex = "0123456789ABCDEF";
  String output;

  for (size_t i = 0; i < input.length(); i++) {
    unsigned char character = input[i];
    if (isalnum(character) || character == '-' || character == '_' || character == '.' || character == '~') {
      output += static_cast<char>(character);
    } else {
      output += '%';
      output += hex[(character >> 4) & 0x0F];
      output += hex[character & 0x0F];
    }
  }
  return output;
}

void openGate() {
  digitalWrite(GATE_RELAY_PIN, HIGH);
  gateRelayUntil = millis() + GATE_OPEN_MS;
}

void haltCard(MFRC522& reader) {
  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();
}

void setSystemConnected(bool connected) {
  if (systemErrorUntil > millis()) {
    return;
  }
  digitalWrite(SYSTEM_GREEN_LED, connected ? HIGH : LOW);
  digitalWrite(SYSTEM_RED_LED, connected ? LOW : HIGH);
}

void flashSystemError() {
  systemErrorUntil = millis() + 1200;
  systemErrorToggleAt = 0;
}

void showReaderResult(const ReaderConfig& readerConfig, bool approved) {
  const unsigned long until = millis() + CARD_RESULT_LED_MS;
  if (readerConfig.reader == &entryReader) {
    entryLedUntil = until;
  } else {
    exitLedUntil = until;
  }
  digitalWrite(readerConfig.greenLed, approved ? HIGH : LOW);
  digitalWrite(readerConfig.redLed, approved ? LOW : HIGH);
}

void serviceOutputs() {
  const unsigned long now = millis();

  if (gateRelayUntil != 0 && (long)(now - gateRelayUntil) >= 0) {
    digitalWrite(GATE_RELAY_PIN, LOW);
    gateRelayUntil = 0;
  }

  if (entryLedUntil != 0 && (long)(now - entryLedUntil) >= 0) {
    digitalWrite(ENTRY_GREEN_LED, LOW);
    digitalWrite(ENTRY_RED_LED, LOW);
    entryLedUntil = 0;
  }

  if (exitLedUntil != 0 && (long)(now - exitLedUntil) >= 0) {
    digitalWrite(EXIT_GREEN_LED, LOW);
    digitalWrite(EXIT_RED_LED, LOW);
    exitLedUntil = 0;
  }

  if (systemErrorUntil > now) {
    if (systemErrorToggleAt == 0 || now >= systemErrorToggleAt) {
      digitalWrite(SYSTEM_GREEN_LED, LOW);
      digitalWrite(SYSTEM_RED_LED, !digitalRead(SYSTEM_RED_LED));
      systemErrorToggleAt = now + 150;
    }
  } else if (systemErrorUntil != 0) {
    systemErrorUntil = 0;
    systemErrorToggleAt = 0;
    setSystemConnected(WiFi.status() == WL_CONNECTED);
  }
}

