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
constexpr uint16_t SERVER_POLL_MS = 200;
constexpr uint16_t HTTP_TIMEOUT_MS = 7000;
constexpr uint16_t CARD_CHECK_DELAY_MS = 10;
constexpr uint16_t CONTINUOUS_SCAN_WINDOW_MS = 120;
constexpr uint16_t CARD_RESULT_LED_MS = 1500;
constexpr uint8_t PROFILE_BLOCK = 4;
constexpr MFRC522::PCD_RxGain RFID_RX_GAIN = MFRC522::RxGain_max;

MFRC522 entryReader(ENTRY_SS_PIN, RC522_RST_PIN);
MFRC522 exitReader(EXIT_SS_PIN, RC522_RST_PIN);
MFRC522::MIFARE_Key keyA;

unsigned long lastServerPoll = 0;
unsigned long lastGateCommandPoll = 0;
unsigned long lastWiFiRetry = 0;

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
bool writeProfile(MFRC522& reader, const String& code);
bool clearProfile(MFRC522& reader);
bool apiGet(const String& path, String& response, int& statusCode);
bool apiPost(const String& path, const String& body, String& response, int& statusCode);
String urlEncode(const String& input);
void openGate();
void haltCard(MFRC522& reader);
void setSystemConnected(bool connected);
void flashSystemError();
void showReaderResult(const ReaderConfig& readerConfig, bool approved);
void processBurn(const String& session, const String& code);
void processContinuousCard(const ReaderConfig& readerConfig);
void processGateCommand();
void initializeReader(MFRC522& reader);

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
  if (WiFi.status() != WL_CONNECTED) {
    setSystemConnected(false);

    if (millis() - lastWiFiRetry >= 5000) {
      lastWiFiRetry = millis();
      Serial.println("Wi-Fi disconnected. Retrying...");
      WiFi.reconnect();
    }
    delay(50);
    return;
  }

  setSystemConnected(true);
  processGateCommand();

  if (millis() - lastServerPoll < SERVER_POLL_MS) {
    return;
  }
  lastServerPoll = millis();

  String response;
  int statusCode = 0;
  if (!apiGet("/api/esp32/poll_rfid.php", response, statusCode)) {
    flashSystemError();
    return;
  }

  bool burnRequested = response.indexOf("\"purpose\":\"burn\"") >= 0;
  if (burnRequested) {
    int start = response.indexOf("\"session_id\":\"");
    if (start < 0) {
      return;
    }
    start += 14;
    int end = response.indexOf('"', start);
    if (end < 0) {
      return;
    }

    String session = response.substring(start, end);
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

  if (response.indexOf("\"scan_requested\":true") >= 0) {
    processContinuousCard(ENTRY_READER);
  }

  processContinuousCard(EXIT_READER);
}

void processBurn(const String& session, const String& code) {
  Serial.println("RFID BURN: Present card on the ENTRY reader...");

  String uid;
  if (!waitForCard(ENTRY_READER, uid, 30000)) {
    Serial.println("ERROR! RFID burn timed out.");
    return;
  }

  if (code == "" || !writeProfile(entryReader, code)) {
    Serial.println("ERROR! Could not write the RFID profile to the card.");
    Serial.println("Server credential was not changed.");
    showReaderResult(ENTRY_READER, false);
    haltCard(entryReader);
    return;
  }

  String response;
  int statusCode = 0;
  String body = "session_id=" + urlEncode(session) + "&rfid_uid=" + urlEncode(uid);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.printf("ERROR! Server response HTTP %d\n", statusCode);
    clearProfile(entryReader);
    showReaderResult(ENTRY_READER, false);
    haltCard(entryReader);
    return;
  }

  if (response.indexOf("\"ok\":true") >= 0) {
    Serial.println("CARD BURNED SUCCESSFULLY: " + code);
    Serial.println("Card updated. You can return to the RFID Management page.");
    showReaderResult(ENTRY_READER, true);
  } else {
    Serial.println("ERROR! RFID profile was not accepted by Smart Gate.");
    clearProfile(entryReader);
    showReaderResult(ENTRY_READER, false);
  }

  haltCard(entryReader);
  waitForCardRemoval(ENTRY_READER, 1500);
}

void processContinuousCard(const ReaderConfig& readerConfig) {
  String uid;
  if (!waitForCard(readerConfig, uid, CONTINUOUS_SCAN_WINDOW_MS)) {
    return;
  }

  Serial.printf("%s RFID DETECTED: %s\n", readerConfig.name, uid.c_str());

  String response;
  int statusCode = 0;
  String body = "session_id=continuous&rfid_uid=" + urlEncode(uid) + "&reader=" + urlEncode(readerConfig.name);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.printf("ERROR! %s RFID validation HTTP %d\n", readerConfig.name, statusCode);
    showReaderResult(readerConfig, false);
    flashSystemError();
    haltCard(*readerConfig.reader);
    waitForCardRemoval(readerConfig, 1200);
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

  haltCard(*readerConfig.reader);
  waitForCardRemoval(readerConfig, 1500);
}

void processGateCommand() {
  if (millis() - lastGateCommandPoll < 150) {
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
  delay(50);
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
    delay(CARD_CHECK_DELAY_MS);
  }
  return false;
}

bool waitForCardRemoval(const ReaderConfig& readerConfig, unsigned long windowMs) {
  unsigned long started = millis();
  while (millis() - started < windowMs) {
    if (!readerConfig.reader->PICC_IsNewCardPresent()) {
      return true;
    }
    delay(CARD_CHECK_DELAY_MS);
  }
  return true;
}

bool authenticateBlock(MFRC522& reader) {
  MFRC522::StatusCode status = reader.PCD_Authenticate(
    MFRC522::PICC_CMD_MF_AUTH_KEY_A,
    PROFILE_BLOCK,
    &keyA,
    &reader.uid
  );

  if (status != MFRC522::STATUS_OK) {
    Serial.println(reader.GetStatusCodeName(status));
    return false;
  }
  return true;
}

bool writeProfile(MFRC522& reader, const String& code) {
  if (!authenticateBlock(reader)) {
    return false;
  }

  byte data[16] = {0};
  data[0] = 'S';
  data[1] = 'G';
  data[2] = '3';
  data[3] = 'R';

  for (uint8_t i = 0; i < 12 && i < code.length(); i++) {
    data[4 + i] = static_cast<byte>(code[i]);
  }

  MFRC522::StatusCode status = reader.MIFARE_Write(PROFILE_BLOCK, data, 16);
  reader.PCD_StopCrypto1();
  return status == MFRC522::STATUS_OK;
}

bool clearProfile(MFRC522& reader) {
  if (!authenticateBlock(reader)) {
    return false;
  }

  byte data[16] = {0};
  MFRC522::StatusCode status = reader.MIFARE_Write(PROFILE_BLOCK, data, 16);
  reader.PCD_StopCrypto1();
  return status == MFRC522::STATUS_OK;
}

bool apiGet(const String& path, String& response, int& statusCode) {
  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(client, base + path)) {
    statusCode = -1;
    return false;
  }

  http.setTimeout(HTTP_TIMEOUT_MS);
  http.addHeader("X-Smart-Gate-Key", SMART_GATE_DEVICE_KEY);
  http.addHeader("X-Smart-Gate-Device", SMART_GATE_DEVICE_ID);

  statusCode = http.GET();
  response = http.getString();
  http.end();
  return statusCode >= 200 && statusCode < 300;
}

bool apiPost(const String& path, const String& body, String& response, int& statusCode) {
  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(client, base + path)) {
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
  delay(GATE_OPEN_MS);
  digitalWrite(GATE_RELAY_PIN, LOW);
}

void haltCard(MFRC522& reader) {
  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();
}

void setSystemConnected(bool connected) {
  digitalWrite(SYSTEM_GREEN_LED, connected ? HIGH : LOW);
  digitalWrite(SYSTEM_RED_LED, connected ? LOW : HIGH);
}

void flashSystemError() {
  digitalWrite(SYSTEM_GREEN_LED, LOW);
  for (uint8_t i = 0; i < 3; i++) {
    digitalWrite(SYSTEM_RED_LED, HIGH);
    delay(150);
    digitalWrite(SYSTEM_RED_LED, LOW);
    delay(150);
  }
  if (WiFi.status() == WL_CONNECTED) {
    digitalWrite(SYSTEM_GREEN_LED, HIGH);
  } else {
    digitalWrite(SYSTEM_RED_LED, HIGH);
  }
}

void showReaderResult(const ReaderConfig& readerConfig, bool approved) {
  digitalWrite(readerConfig.greenLed, approved ? HIGH : LOW);
  digitalWrite(readerConfig.redLed, approved ? LOW : HIGH);
  delay(CARD_RESULT_LED_MS);
  digitalWrite(readerConfig.greenLed, LOW);
  digitalWrite(readerConfig.redLed, LOW);
}
