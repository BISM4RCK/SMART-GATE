/*
 * Smart Gate — ESP32 + RC522 RFID bridge
 * BISM4RCK-KUN3H0 2026
 *
 * Arduino IDE libraries: MFRC522
 * RC522 VSPI: SDA/SS=5, SCK=18, MOSI=23, MISO=19, RST=22, 3V3, GND
 * Gate relay output: GPIO 27 (active HIGH)
 *
 * The RC522 continuously listens for cards. A detected UID is sent to Smart
 * Gate for validation. The relay is activated only for an approved result.
 * Burn operations requested by the admin take priority over continuous scans.
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
constexpr uint8_t RC522_SS_PIN=5, RC522_RST_PIN=22, GATE_RELAY_PIN=27;
constexpr uint32_t GATE_OPEN_MS=2500;
constexpr uint16_t SERVER_POLL_MS=500, HTTP_TIMEOUT_MS=7000, CONTINUOUS_SCAN_WINDOW_MS=900;
constexpr uint8_t PROFILE_BLOCK=4;
MFRC522 rfid(RC522_SS_PIN,RC522_RST_PIN); MFRC522::MIFARE_Key keyA; unsigned long lastPoll=0;
String uidString(); bool waitForCard(String& uid,unsigned long windowMs); bool waitForCardRemoval(unsigned long windowMs);
bool authenticateBlock(); bool writeProfile(const String& code); bool clearProfile();
bool apiGet(const String& path,String& response,int& statusCode); bool apiPost(const String& path,const String& body,String& response,int& statusCode);
String urlEncode(const String& input); void openGate(); void haltCard(); void processBurn(const String& session,const String& code); void processContinuousCard(); void processGateCommand();

void setup(){
  Serial.begin(115200); delay(500); pinMode(GATE_RELAY_PIN,OUTPUT); digitalWrite(GATE_RELAY_PIN,LOW);
  SPI.begin(); rfid.PCD_Init(); for(byte i=0;i<6;i++)keyA.keyByte[i]=0xFF;
  WiFi.mode(WIFI_STA); // BISM4RCK-KUN3H0 2026
  // TP_link1 is a hidden SSID; true requests hidden-network association.
  WiFi.begin(WIFI_SSID,WIFI_PASSWORD,0,nullptr,true);
  Serial.println("\n=== Smart Gate RFID Scanner ==="); Serial.println("Device ID: 180503");
  unsigned long started=millis(); while(WiFi.status()!=WL_CONNECTED&&millis()-started<20000){delay(300);Serial.print('.');} Serial.println();
  Serial.println(WiFi.status()==WL_CONNECTED?"Wi-Fi connected.":"ERROR! Wi-Fi connection failed."); Serial.println("RFID scanner: ALWAYS LISTENING");
}

void loop(){
  if(WiFi.status()!=WL_CONNECTED){WiFi.reconnect();delay(1000);return;}
  processGateCommand();
  if(millis()-lastPoll<SERVER_POLL_MS)return; lastPoll=millis();
  String response; int status;
  if(!apiGet("/api/esp32/poll_rfid.php",response,status))return;
  bool burn=response.indexOf("\"purpose\":\"burn\"")>=0;
  if(burn){
    int p=response.indexOf("\"session_id\":\""); if(p<0)return; p+=14; int q=response.indexOf('"',p); if(q<0)return;
    String session=response.substring(p,q), code=""; int cp=response.indexOf("\"credential_code\":\""); if(cp>=0){cp+=19;int cq=response.indexOf('"',cp);if(cq>cp)code=response.substring(cp,cq);}
    processBurn(session,code); return;
  }
  if(response.indexOf("\"scan_requested\":true")>=0)processContinuousCard();
}

void processBurn(const String& session,const String& code){
  Serial.println("RFID BURN: Present card..."); String uid;
  if(!waitForCard(uid,30000)){Serial.println("ERROR! RFID scan timed out.");return;}
  if(code==""||!writeProfile(code)){Serial.println("ERROR! Could not write the RFID profile to the card. Server credential was not changed.");haltCard();return;}
  String response; int status; String body="session_id="+urlEncode(session)+"&rfid_uid="+urlEncode(uid);
  if(!apiPost("/api/esp32/submit_rfid_scan.php",body,response,status)){Serial.printf("ERROR! Server response HTTP %d\n",status);clearProfile();haltCard();return;}
  if(response.indexOf("\"ok\":true")>=0){Serial.println("CARD BURNED SUCCESSFULLY: "+code);Serial.println("Card updated. You can return to the RFID Management page.");}else{Serial.println("ERROR! RFID profile was not accepted by Smart Gate.");clearProfile();}
  haltCard(); waitForCardRemoval(1500);
}

void processContinuousCard(){
  String uid; if(!waitForCard(uid,CONTINUOUS_SCAN_WINDOW_MS))return;
  Serial.println("RFID DETECTED: "+uid);
  String response; int status; String body="session_id=continuous&rfid_uid="+urlEncode(uid);
  if(!apiPost("/api/esp32/submit_rfid_scan.php",body,response,status)){Serial.printf("ERROR! RFID validation HTTP %d\n",status);haltCard();waitForCardRemoval(1200);return;}
  if(response.indexOf("\"gate_opened\":true")>=0){Serial.println("GATE OPENED");openGate();}
  else if(response.indexOf("\"gate_status\":\"pending\"")>=0)Serial.println("REQUEST STILL PENDING");
  else Serial.println("DENIED");
  haltCard(); waitForCardRemoval(1500);
}

void processGateCommand(){
  if(millis()-lastPoll<150)return; // keep command polling lightweight
  String response; int status; if(!apiGet("/api/esp32/gate_command.php",response,status))return;
  if(response.indexOf("\"command\":null")>=0||response.indexOf("\"command\":false")>=0)return;
  int p=response.indexOf("\"id\":"); if(p<0)return; p+=5; int q=p; while(q<(int)response.length()&&isDigit(response[q]))q++; if(q<=p)return; String id=response.substring(p,q);
  if(response.indexOf("\"command\":\"open_gate\"")<0)return;
  Serial.println("GATE COMMAND RECEIVED: "+id); openGate(); String out; int outStatus; String body="command_id="+urlEncode(id); apiPost("/api/esp32/complete_gate_command.php",body,out,outStatus); Serial.println("GATE OPENED");
}

String uidString(){String out;for(byte i=0;i<rfid.uid.size;i++){if(i)out+=':';if(rfid.uid.uidByte[i]<0x10)out+='0';out+=String(rfid.uid.uidByte[i],HEX);}out.toUpperCase();return out;}
bool waitForCard(String& uid,unsigned long windowMs){unsigned long started=millis();while(millis()-started<windowMs){if(rfid.PICC_IsNewCardPresent()&&rfid.PICC_ReadCardSerial()){uid=uidString();return true;}delay(30);}return false;}
bool waitForCardRemoval(unsigned long windowMs){unsigned long started=millis();while(millis()-started<windowMs){if(!rfid.PICC_IsNewCardPresent())return true;delay(30);}return true;}
bool authenticateBlock(){auto st=rfid.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A,PROFILE_BLOCK,&keyA,&rfid.uid);if(st!=MFRC522::STATUS_OK){Serial.println(rfid.GetStatusCodeName(st));return false;}return true;}
bool writeProfile(const String& code){if(!authenticateBlock())return false;byte data[16]={0};data[0]='S';data[1]='G';data[2]='3';data[3]='R';for(uint8_t i=0;i<12&&i<code.length();i++)data[4+i]=(byte)code[i];auto st=rfid.MIFARE_Write(PROFILE_BLOCK,data,16);rfid.PCD_StopCrypto1();return st==MFRC522::STATUS_OK;}
bool clearProfile(){if(!authenticateBlock())return false;byte data[16]={0};auto st=rfid.MIFARE_Write(PROFILE_BLOCK,data,16);rfid.PCD_StopCrypto1();return st==MFRC522::STATUS_OK;}
bool apiGet(const String& path,String& response,int& statusCode){WiFiClientSecure client;client.setInsecure();HTTPClient http;String base=SMART_GATE_BASE_URL;while(base.endsWith("/"))base.remove(base.length()-1);http.begin(client,base+path);http.setTimeout(HTTP_TIMEOUT_MS);http.addHeader("X-Smart-Gate-Key",SMART_GATE_DEVICE_KEY);http.addHeader("X-Smart-Gate-Device",SMART_GATE_DEVICE_ID);statusCode=http.GET();response=http.getString();http.end();return statusCode>=200&&statusCode<300;}
bool apiPost(const String& path,const String& body,String& response,int& statusCode){WiFiClientSecure client;client.setInsecure();HTTPClient http;String base=SMART_GATE_BASE_URL;while(base.endsWith("/"))base.remove(base.length()-1);http.begin(client,base+path);http.setTimeout(HTTP_TIMEOUT_MS);http.addHeader("Content-Type","application/x-www-form-urlencoded");http.addHeader("X-Smart-Gate-Key",SMART_GATE_DEVICE_KEY);http.addHeader("X-Smart-Gate-Device",SMART_GATE_DEVICE_ID);statusCode=http.POST(body);response=http.getString();http.end();return statusCode>=200&&statusCode<300;}
String urlEncode(const String& input){const char*hex="0123456789ABCDEF";String out;for(size_t i=0;i<input.length();i++){unsigned char c=input[i];if(isalnum(c)||c=='-'||c=='_'||c=='.'||c=='~')out+=(char)c;else{out+='%';out+=hex[(c>>4)&15];out+=hex[c&15];}}return out;}
void openGate(){digitalWrite(GATE_RELAY_PIN,HIGH);delay(GATE_OPEN_MS);digitalWrite(GATE_RELAY_PIN,LOW);} void haltCard(){rfid.PICC_HaltA();rfid.PCD_StopCrypto1();}
