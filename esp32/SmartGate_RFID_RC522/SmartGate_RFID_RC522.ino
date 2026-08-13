/*
 * Smart Gate - ESP32 + dual RC522 RFID controller
 * Rewritten from scratch while preserving the existing Smart Gate API,
 * Wi-Fi behavior, gate control, LEDs, GPIO assignments, and RFID features.
 *
 * GPIO (UNCHANGED)
 * SPI: SCK 18, MOSI 23, MISO 19, RST 22
 * Entry RC522 SS: 5
 * Exit  RC522 SS: 16
 * Entry LEDs: green 25, red 26
 * Exit LEDs:  green 32, red 33
 * System LEDs: green 13, red 14
 * Gate relay: 27
 *
 * RFID burn transaction:
 *   detect -> select -> authenticate ONCE -> write -> verify -> halt/stop
 *   -> wait for physical removal -> report to server -> normal scanning
 *
 * IMPORTANT:
 *   The burn path deliberately does NOT reselect, reauthenticate, or
 *   reinitialize the card after a write failure/success. A failed
 *   authentication is a failed transaction, not a retry loop.
 */

#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <SPI.h>
#include <MFRC522.h>

// -----------------------------------------------------------------------------
// Smart Gate connection settings - carried over unchanged
// -----------------------------------------------------------------------------
const char* WIFI_SSID = "TP_link1";
const char* WIFI_PASSWORD = "Benq2877";
const char* SMART_GATE_BASE_URL = "https://docile-darkroom-sinless.ngrok-free.dev/smart-gate/";
const char* SMART_GATE_DEVICE_KEY = "2jGpAbQGBVW9qJU89UQjDxNAjNMtj2q-JsuvL9dE8Ig";
const char* SMART_GATE_DEVICE_ID = "180503";

// -----------------------------------------------------------------------------
// GPIO - DO NOT CHANGE
// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
// Timing / RFID settings
// -----------------------------------------------------------------------------
constexpr uint32_t GATE_OPEN_MS = 2500;
constexpr uint32_t BURN_CARD_WAIT_MS = 30000;
constexpr uint32_t BURN_REMOVAL_WAIT_MS = 15000;
constexpr uint32_t BURN_REMOVED_STABLE_MS = 300;
constexpr uint32_t SERVER_POLL_MS = 650;
constexpr uint32_t GATE_COMMAND_POLL_MS = 350;
constexpr uint32_t HTTP_TIMEOUT_MS = 3500;
constexpr uint32_t HTTP_CONNECT_TIMEOUT_MS = 2000;
constexpr uint32_t CARD_RESULT_LED_MS = 1500;
constexpr uint32_t SCAN_COOLDOWN_MS = 650;
constexpr uint32_t WIFI_RETRY_MS = 5000;
constexpr uint8_t PROFILE_BLOCK = 4;
constexpr uint8_t PROFILE_BLOCK_COUNT = 3;
constexpr uint8_t PROFILE_BYTES_PER_BLOCK = 16;
constexpr uint8_t PROFILE_HEADER_BYTES = 4;
constexpr MFRC522::PCD_RxGain RFID_RX_GAIN = MFRC522::RxGain_max;

// -----------------------------------------------------------------------------
// Hardware objects
// -----------------------------------------------------------------------------
MFRC522 entryReader(ENTRY_SS_PIN, RC522_RST_PIN);
MFRC522 exitReader(EXIT_SS_PIN, RC522_RST_PIN);
MFRC522::MIFARE_Key defaultKeyA;
WiFiClientSecure secureClient;

struct ReaderConfig {
  const char* name;
  MFRC522* reader;
  uint8_t greenLed;
  uint8_t redLed;
};

const ReaderConfig ENTRY_READER = {"entry", &entryReader, ENTRY_GREEN_LED, ENTRY_RED_LED};
const ReaderConfig EXIT_READER  = {"exit",  &exitReader,  EXIT_GREEN_LED,  EXIT_RED_LED};

// -----------------------------------------------------------------------------
// Runtime state
// -----------------------------------------------------------------------------
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
String lastFailedBurnSession = "";

// -----------------------------------------------------------------------------
// Utility declarations
// -----------------------------------------------------------------------------
String uidString(const MFRC522& reader);
String piccTypeString(MFRC522::PICC_Type type);
String urlEncode(const String& input);

bool apiGet(const String& path, String& response, int& statusCode);
bool apiPost(const String& path, const String& body, String& response, int& statusCode);

void initializeReader(MFRC522& reader, const char* name);
void haltCard(MFRC522& reader);
bool cardPresent(MFRC522& reader);
bool waitForCard(const ReaderConfig& cfg, String& uid, unsigned long timeoutMs);
bool waitForCardRemoval(MFRC522& reader, unsigned long timeoutMs);

bool authenticateForBurn(MFRC522& reader);
bool buildProfile(const String& code, byte data[PROFILE_BLOCK_COUNT][PROFILE_BYTES_PER_BLOCK]);
bool writeAndVerifyProfile(MFRC522& reader, const String& code);

bool reportBurnFailure(const String& session, const String& uid, const String& reason);
void processBurn(const String& session, const String& credentialCode);

void scanReader(const ReaderConfig& cfg, unsigned long& lastScanAt);
void pollBurnRequest();
void processGateCommand();

void openGate();
void showReaderResult(const ReaderConfig& cfg, bool approved);
void flashSystemError();
void setSystemConnected(bool connected);
void serviceOutputs();

// -----------------------------------------------------------------------------
// Setup
// -----------------------------------------------------------------------------
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

  // One shared SPI bus, two chip-select lines, same RST as the original build.
  SPI.begin(SPI_SCK_PIN, SPI_MISO_PIN, SPI_MOSI_PIN);
  initializeReader(entryReader, "ENTRY");
  initializeReader(exitReader, "EXIT");

  // Factory MIFARE Classic Key A. This is intentionally the only automatic
  // authentication key used by the burn transaction. No Key-A/Key-B retry loop.
  for (byte i = 0; i < 6; ++i) {
    defaultKeyA.keyByte[i] = 0xFF;
  }

  secureClient.setInsecure();
  secureClient.setTimeout(HTTP_TIMEOUT_MS);

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD, 0, nullptr, true);

  Serial.println();
  Serial.println("=== Smart Gate Dual RFID Controller ===");
  Serial.println("Firmware: clean rewrite");
  Serial.println("Device ID: 180503");
  Serial.println("Entry RC522 SS: GPIO 5");
  Serial.println("Exit RC522 SS: GPIO 16");
  Serial.println("SPI: SCK 18 / MISO 19 / MOSI 23 / RST 22");
  Serial.println("RFID readers: ALWAYS LISTENING");

  const unsigned long started = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - started < 20000UL) {
    delay(300);
    Serial.print('.');
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    setSystemConnected(true);
    Serial.println("Wi-Fi connected.");
  } else {
    setSystemConnected(false);
    Serial.println("ERROR! Wi-Fi connection failed; retry will continue in loop.");
  }
}

// -----------------------------------------------------------------------------
// Main loop
// -----------------------------------------------------------------------------
void loop() {
  serviceOutputs();

  if (WiFi.status() != WL_CONNECTED) {
    setSystemConnected(false);
    if (millis() - lastWiFiRetry >= WIFI_RETRY_MS) {
      lastWiFiRetry = millis();
      Serial.println("Wi-Fi disconnected. Reconnecting...");
      WiFi.reconnect();
    }
    delay(5);
    return;
  }

  setSystemConnected(true);
  processGateCommand();
  pollBurnRequest();

  // Normal gate scanning continues when no burn transaction is active.
  scanReader(ENTRY_READER, entryLastScanAt);
  scanReader(EXIT_READER, exitLastScanAt);
}

// -----------------------------------------------------------------------------
// Reader initialization
// -----------------------------------------------------------------------------
void initializeReader(MFRC522& reader, const char* name) {
  reader.PCD_Init();
  delay(80);
  reader.PCD_SetAntennaGain(RFID_RX_GAIN);
  reader.PCD_AntennaOn();

  Serial.print(name);
  Serial.println(" RC522 initialized.");
}

// -----------------------------------------------------------------------------
// Normal gate RFID scanning
// -----------------------------------------------------------------------------
void scanReader(const ReaderConfig& cfg, unsigned long& lastScanAt) {
  if (millis() - lastScanAt < SCAN_COOLDOWN_MS) {
    return;
  }

  MFRC522& reader = *cfg.reader;
  if (!reader.PICC_IsNewCardPresent()) {
    return;
  }

  delay(20);
  if (!reader.PICC_ReadCardSerial()) {
    return;
  }

  lastScanAt = millis();
  const String uid = uidString(reader);
  Serial.print(cfg.name);
  Serial.print(" RFID DETECTED: ");
  Serial.println(uid);

  String response;
  int statusCode = 0;
  const String body =
    "session_id=continuous&rfid_uid=" + urlEncode(uid) +
    "&reader=" + urlEncode(cfg.name);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.print("ERROR! ");
    Serial.print(cfg.name);
    Serial.print(" RFID validation HTTP ");
    Serial.println(statusCode);
    showReaderResult(cfg, false);
    flashSystemError();
    haltCard(reader);
    return;
  }

  const bool approved = response.indexOf("\"gate_opened\":true") >= 0;
  const bool pending = response.indexOf("\"gate_status\":\"pending\"") >= 0;

  if (approved) {
    Serial.print(cfg.name);
    Serial.println(": GATE OPENED");
    showReaderResult(cfg, true);
    openGate();
  } else if (pending) {
    Serial.print(cfg.name);
    Serial.println(": REQUEST STILL PENDING");
    showReaderResult(cfg, false);
  } else {
    Serial.print(cfg.name);
    Serial.println(": DENIED");
    showReaderResult(cfg, false);
  }

  haltCard(reader);
}

// -----------------------------------------------------------------------------
// Burn-session polling
// -----------------------------------------------------------------------------
void pollBurnRequest() {
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

  if (response.indexOf("\"purpose\":\"burn\"") < 0) {
    return;
  }

  const int sessionMarker = response.indexOf("\"session_id\":\"");
  if (sessionMarker < 0) {
    return;
  }

  const int sessionStart = sessionMarker + 14;
  const int sessionEnd = response.indexOf('"', sessionStart);
  if (sessionEnd <= sessionStart) {
    return;
  }

  const String session = response.substring(sessionStart, sessionEnd);
  if (session == lastFailedBurnSession) {
    return;
  }

  const int codeMarker = response.indexOf("\"credential_code\":\"");
  if (codeMarker < 0) {
    Serial.println("ERROR! Burn session has no credential_code.");
    reportBurnFailure(session, "UNKNOWN", "Burn session did not contain credential_code.");
    lastFailedBurnSession = session;
    return;
  }

  const int codeStart = codeMarker + 19;
  const int codeEnd = response.indexOf('"', codeStart);
  if (codeEnd <= codeStart) {
    Serial.println("ERROR! Burn session credential_code is empty.");
    reportBurnFailure(session, "UNKNOWN", "Burn session credential_code was empty.");
    lastFailedBurnSession = session;
    return;
  }

  const String credentialCode = response.substring(codeStart, codeEnd);
  processBurn(session, credentialCode);
}

// -----------------------------------------------------------------------------
// RFID burn transaction - deliberately linear and single-pass
// -----------------------------------------------------------------------------
void processBurn(const String& session, const String& credentialCode) {
  Serial.println();
  Serial.println("=== RFID BURN TRANSACTION START ===");
  Serial.println("Present the RFID card on the ENTRY reader.");
  Serial.println("The card will be written once and must then be removed.");

  if (credentialCode.length() == 0) {
    Serial.println("ERROR! Credential code is empty. No RFID transaction started.");
    showReaderResult(ENTRY_READER, false);
    reportBurnFailure(session, "UNKNOWN", "Credential code was empty.");
    lastFailedBurnSession = session;
    Serial.println("=== RFID BURN TRANSACTION END (FAILED) ===");
    return;
  }

  String uid;
  if (!waitForCard(ENTRY_READER, uid, BURN_CARD_WAIT_MS)) {
    Serial.println("ERROR! RFID card was not detected within 30 seconds.");
    showReaderResult(ENTRY_READER, false);
    reportBurnFailure(session, "UNKNOWN", "RFID card was not detected within 30 seconds.");
    lastFailedBurnSession = session;
    Serial.println("=== RFID BURN TRANSACTION END (FAILED) ===");
    return;
  }

  MFRC522& reader = entryReader;

  Serial.print("RFID card selected. UID: ");
  Serial.println(uid);
  Serial.print("RFID card type: ");
  Serial.println(piccTypeString(reader.PICC_GetType(reader.uid.sak)));

  // We need MIFARE Classic sector/block authentication for blocks 4-6.
  const MFRC522::PICC_Type cardType = reader.PICC_GetType(reader.uid.sak);
  if (cardType != MFRC522::PICC_TYPE_MIFARE_MINI &&
      cardType != MFRC522::PICC_TYPE_MIFARE_1K &&
      cardType != MFRC522::PICC_TYPE_MIFARE_4K) {
    Serial.println("ERROR! This card is not a MIFARE Classic card suitable for this burn format.");
    haltCard(reader);
    showReaderResult(ENTRY_READER, false);
    waitForCardRemoval(reader, BURN_REMOVAL_WAIT_MS);
    reportBurnFailure(session, uid, "Unsupported RFID card type. Smart Gate burn requires MIFARE Classic.");
    lastFailedBurnSession = session;
    Serial.println("=== RFID BURN TRANSACTION END (FAILED) ===");
    return;
  }

  Serial.println("RFID: authenticating ONCE with Key A...");
  if (!authenticateForBurn(reader)) {
    // Authentication failure is terminal for this physical transaction.
    // No Key B attempt. No reselect. No reinitialize. No retry loop.
    Serial.println("ERROR! RFID authentication failed. Write was NOT attempted.");
    haltCard(reader);
    showReaderResult(ENTRY_READER, false);
    Serial.println("RFID transaction ended. Remove the card.");
    waitForCardRemoval(reader, BURN_REMOVAL_WAIT_MS);
    reportBurnFailure(session, uid, "RFID authentication failed with the configured Key A. Write was not attempted.");
    lastFailedBurnSession = session;
    Serial.println("=== RFID BURN TRANSACTION END (FAILED) ===");
    return;
  }

  Serial.println("RFID authentication successful.");
  Serial.println("RFID: writing profile blocks 4, 5, 6...");

  const bool writeOk = writeAndVerifyProfile(reader, credentialCode);

  // End the RFID transaction exactly once, regardless of write result.
  haltCard(reader);

  if (!writeOk) {
    Serial.println("ERROR! RFID profile write/verification failed.");
    showReaderResult(ENTRY_READER, false);
    Serial.println("RFID transaction ended. Remove the card.");
    waitForCardRemoval(reader, BURN_REMOVAL_WAIT_MS);
    reportBurnFailure(session, uid, "RFID profile write or read-back verification failed.");
    lastFailedBurnSession = session;
    Serial.println("=== RFID BURN TRANSACTION END (FAILED) ===");
    return;
  }

  Serial.println("RFID profile written and verified successfully.");
  showReaderResult(ENTRY_READER, true);

  // IMPORTANT: server assignment happens only after the physical card
  // transaction has been ended and the card has been removed.
  Serial.println("RFID transaction ended. Remove the card.");
  if (!waitForCardRemoval(reader, BURN_REMOVAL_WAIT_MS)) {
    Serial.println("WARNING! Card removal was not detected before timeout.");
  }

  String response;
  int statusCode = 0;
  const String body =
    "session_id=" + urlEncode(session) +
    "&rfid_uid=" + urlEncode(uid) +
    "&assignment_mode=uid";

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.print("ERROR! Burn was physically successful, but server confirmation failed. HTTP ");
    Serial.println(statusCode);
    Serial.println("The card remains programmed; do not rewrite it unless the website still shows the session as pending.");
    showReaderResult(ENTRY_READER, false);
    Serial.println("RFID burn session closed. Returning to gate function.");
    Serial.println("=== RFID BURN TRANSACTION END (SERVER ERROR) ===");
    return;
  }

  if (response.indexOf("\"ok\":true") >= 0 && response.indexOf("\"write_profile\":true") >= 0) {
    Serial.print("RFID BURN SUCCESSFUL: ");
    Serial.println(uid);
    showReaderResult(ENTRY_READER, true);
  } else {
    Serial.println("ERROR! Smart Gate rejected the RFID burn completion.");
    Serial.println("Server response:");
    Serial.println(response);
    showReaderResult(ENTRY_READER, false);
  }

  Serial.println("RFID burn session closed. Returning to gate function.");
  Serial.println("=== RFID BURN TRANSACTION END ===");
}

// -----------------------------------------------------------------------------
// Burn write/authentication
// -----------------------------------------------------------------------------
bool authenticateForBurn(MFRC522& reader) {
  const MFRC522::StatusCode status = reader.PCD_Authenticate(
    MFRC522::PICC_CMD_MF_AUTH_KEY_A,
    PROFILE_BLOCK,
    &defaultKeyA,
    &reader.uid
  );

  if (status != MFRC522::STATUS_OK) {
    Serial.print("RFID authentication failed with Key A: ");
    Serial.println(reader.GetStatusCodeName(status));
    return false;
  }

  return true;
}

bool buildProfile(const String& code, byte data[PROFILE_BLOCK_COUNT][PROFILE_BYTES_PER_BLOCK]) {
  const uint16_t maxLength =
    PROFILE_BLOCK_COUNT * PROFILE_BYTES_PER_BLOCK - PROFILE_HEADER_BYTES;

  if (code.length() > maxLength) {
    Serial.print("ERROR! Credential code too long: ");
    Serial.print(code.length());
    Serial.print(" / ");
    Serial.println(maxLength);
    return false;
  }

  memset(data, 0, PROFILE_BLOCK_COUNT * PROFILE_BYTES_PER_BLOCK);

  data[0][0] = 'S';
  data[0][1] = 'G';
  data[0][2] = '4';
  data[0][3] = 'R';

  for (uint16_t i = 0; i < code.length(); ++i) {
    const uint16_t position = PROFILE_HEADER_BYTES + i;
    data[position / PROFILE_BYTES_PER_BLOCK][position % PROFILE_BYTES_PER_BLOCK] =
      static_cast<byte>(code[i]);
  }

  return true;
}

bool writeAndVerifyProfile(MFRC522& reader, const String& code) {
  byte expected[PROFILE_BLOCK_COUNT][PROFILE_BYTES_PER_BLOCK];
  if (!buildProfile(code, expected)) {
    return false;
  }

  // Authentication has already succeeded. Keep the same crypto session while
  // writing all three data blocks. Never re-authenticate between blocks.
  for (uint8_t offset = 0; offset < PROFILE_BLOCK_COUNT; ++offset) {
    const uint8_t block = PROFILE_BLOCK + offset;
    const MFRC522::StatusCode status = reader.MIFARE_Write(
      block,
      expected[offset],
      PROFILE_BYTES_PER_BLOCK
    );

    if (status != MFRC522::STATUS_OK) {
      Serial.print("ERROR! RFID write failed on block ");
      Serial.print(block);
      Serial.print(": ");
      Serial.println(reader.GetStatusCodeName(status));
      return false;
    }

    Serial.print("  block ");
    Serial.print(block);
    Serial.println(" written.");
  }

  // Read-back verification uses the same authenticated transaction.
  // This catches a card that accepted a command but did not store the data.
  Serial.println("RFID: verifying written blocks...");

  byte actual[18];
  byte actualSize = sizeof(actual);

  for (uint8_t offset = 0; offset < PROFILE_BLOCK_COUNT; ++offset) {
    const uint8_t block = PROFILE_BLOCK + offset;
    actualSize = sizeof(actual);

    const MFRC522::StatusCode status = reader.MIFARE_Read(
      block,
      actual,
      &actualSize
    );

    if (status != MFRC522::STATUS_OK) {
      Serial.print("ERROR! RFID read-back failed on block ");
      Serial.print(block);
      Serial.print(": ");
      Serial.println(reader.GetStatusCodeName(status));
      return false;
    }

    if (actualSize < PROFILE_BYTES_PER_BLOCK ||
        memcmp(actual, expected[offset], PROFILE_BYTES_PER_BLOCK) != 0) {
      Serial.print("ERROR! RFID verification mismatch on block ");
      Serial.println(block);
      return false;
    }

    Serial.print("  block ");
    Serial.print(block);
    Serial.println(" verified.");
  }

  return true;
}

// -----------------------------------------------------------------------------
// Card detection / removal
// -----------------------------------------------------------------------------
bool cardPresent(MFRC522& reader) {
  return reader.PICC_IsNewCardPresent() && reader.PICC_ReadCardSerial();
}

bool waitForCard(const ReaderConfig& cfg, String& uid, unsigned long timeoutMs) {
  const unsigned long started = millis();

  while (millis() - started < timeoutMs) {
    if (cardPresent(*cfg.reader)) {
      uid = uidString(*cfg.reader);
      return true;
    }
    delay(10);
  }

  return false;
}

bool waitForCardRemoval(MFRC522& reader, unsigned long timeoutMs) {
  const unsigned long started = millis();
  unsigned long absentSince = 0;

  while (millis() - started < timeoutMs) {
    // After PICC_HaltA(), PICC_IsNewCardPresent() should remain false until
    // the PICC is physically removed/re-presented. We deliberately do not
    // call PICC_ReadCardSerial() here because that would reselect the card.
    if (!reader.PICC_IsNewCardPresent()) {
      if (absentSince == 0) {
        absentSince = millis();
      }
      if (millis() - absentSince >= BURN_REMOVED_STABLE_MS) {
        return true;
      }
    } else {
      absentSince = 0;
    }
    delay(20);
  }

  return false;
}

void haltCard(MFRC522& reader) {
  reader.PICC_HaltA();
  reader.PCD_StopCrypto1();
}

// -----------------------------------------------------------------------------
// Gate command handling
// -----------------------------------------------------------------------------
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

  if (response.indexOf("\"command\":null") >= 0 ||
      response.indexOf("\"command\":false") >= 0) {
    return;
  }

  const int idMarker = response.indexOf("\"id\":");
  if (idMarker < 0 || response.indexOf("\"command\":\"open_gate\"") < 0) {
    return;
  }

  const int idStart = idMarker + 5;
  int idEnd = idStart;
  while (idEnd < static_cast<int>(response.length()) && isDigit(response[idEnd])) {
    ++idEnd;
  }

  if (idEnd <= idStart) {
    return;
  }

  const String commandId = response.substring(idStart, idEnd);
  Serial.print("GATE COMMAND RECEIVED: ");
  Serial.println(commandId);

  openGate();

  String output;
  int outputStatus = 0;
  const String body = "command_id=" + urlEncode(commandId);

  if (!apiPost("/api/esp32/complete_gate_command.php", body, output, outputStatus)) {
    flashSystemError();
    return;
  }

  Serial.println("GATE OPENED");
}

// -----------------------------------------------------------------------------
// HTTP API
// -----------------------------------------------------------------------------
bool apiGet(const String& path, String& response, int& statusCode) {
  HTTPClient http;

  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(secureClient, base + path)) {
    statusCode = -1;
    response = "";
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

bool apiPost(const String& path, const String& body, String& response, int& statusCode) {
  HTTPClient http;

  String base = SMART_GATE_BASE_URL;
  while (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  if (!http.begin(secureClient, base + path)) {
    statusCode = -1;
    response = "";
    return false;
  }

  http.setTimeout(HTTP_TIMEOUT_MS);
  http.setConnectTimeout(HTTP_CONNECT_TIMEOUT_MS);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.addHeader("X-Smart-Gate-Key", SMART_GATE_DEVICE_KEY);
  http.addHeader("X-Smart-Gate-Device", SMART_GATE_DEVICE_ID);

  statusCode = http.POST(body);
  response = http.getString();
  http.end();

  return statusCode >= 200 && statusCode < 300;
}

bool reportBurnFailure(const String& session, const String& uid, const String& reason) {
  String response;
  int statusCode = 0;

  const String safeUid = uid.length() ? uid : "00000000";
  const String body =
    "session_id=" + urlEncode(session) +
    "&rfid_uid=" + urlEncode(safeUid) +
    "&burn_failed=1" +
    "&failure_reason=" + urlEncode(reason);

  if (!apiPost("/api/esp32/submit_rfid_scan.php", body, response, statusCode)) {
    Serial.print("ERROR! Could not report RFID burn failure. HTTP ");
    Serial.println(statusCode);
    return false;
  }

  Serial.println("RFID burn failure reported to Smart Gate.");
  return true;
}

// -----------------------------------------------------------------------------
// Outputs
// -----------------------------------------------------------------------------
void openGate() {
  digitalWrite(GATE_RELAY_PIN, HIGH);
  gateRelayUntil = millis() + GATE_OPEN_MS;
}

void showReaderResult(const ReaderConfig& cfg, bool approved) {
  const unsigned long until = millis() + CARD_RESULT_LED_MS;

  if (cfg.reader == &entryReader) {
    entryLedUntil = until;
  } else {
    exitLedUntil = until;
  }

  digitalWrite(cfg.greenLed, approved ? HIGH : LOW);
  digitalWrite(cfg.redLed, approved ? LOW : HIGH);
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

void serviceOutputs() {
  const unsigned long now = millis();

  if (gateRelayUntil != 0 && static_cast<long>(now - gateRelayUntil) >= 0) {
    digitalWrite(GATE_RELAY_PIN, LOW);
    gateRelayUntil = 0;
  }

  if (entryLedUntil != 0 && static_cast<long>(now - entryLedUntil) >= 0) {
    digitalWrite(ENTRY_GREEN_LED, LOW);
    digitalWrite(ENTRY_RED_LED, LOW);
    entryLedUntil = 0;
  }

  if (exitLedUntil != 0 && static_cast<long>(now - exitLedUntil) >= 0) {
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

// -----------------------------------------------------------------------------
// String helpers
// -----------------------------------------------------------------------------
String uidString(const MFRC522& reader) {
  String output;

  for (byte i = 0; i < reader.uid.size; ++i) {
    if (i > 0) {
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

String piccTypeString(MFRC522::PICC_Type type) {
  switch (type) {
    case MFRC522::PICC_TYPE_MIFARE_MINI: return "MIFARE Mini";
    case MFRC522::PICC_TYPE_MIFARE_1K: return "MIFARE Classic 1K";
    case MFRC522::PICC_TYPE_MIFARE_4K: return "MIFARE Classic 4K";
    case MFRC522::PICC_TYPE_MIFARE_UL: return "MIFARE Ultralight";
    case MFRC522::PICC_TYPE_MIFARE_PLUS: return "MIFARE Plus";
    case MFRC522::PICC_TYPE_MIFARE_DESFIRE: return "MIFARE DESFire";
    case MFRC522::PICC_TYPE_TNP3XXX: return "MIFARE TNP3XXX";
    case MFRC522::PICC_TYPE_NOT_COMPLETE: return "Unknown / not complete";
    default: return "Unknown";
  }
}

String urlEncode(const String& input) {
  const char* hex = "0123456789ABCDEF";
  String output;

  for (size_t i = 0; i < input.length(); ++i) {
    const unsigned char c = static_cast<unsigned char>(input[i]);
    if (isalnum(c) || c == '-' || c == '_' || c == '.' || c == '~') {
      output += static_cast<char>(c);
    } else {
      output += '%';
      output += hex[(c >> 4) & 0x0F];
      output += hex[c & 0x0F];
    }
  }

  return output;
}
