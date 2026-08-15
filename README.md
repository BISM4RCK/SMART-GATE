# ARCHIVED

GOLDEN HOMES Subdivision

A lightweight MVC PHP system for a gated community, built for XAMPP and MySQL.

## Setup
1. Put the folder in `C:\xampp\htdocs\smart-gate`
2. Start Apache and MySQL in XAMPP
3. Import `database/smart_gate.sql` in phpMyAdmin
4. Open `http://YOUR-PC-IP/smart-gate/` or `http://localhost/smart-gate/`

## Demo Accounts

All demo accounts below use the password:

- `Password123!`

### Residents

| Account | House | Vehicles |
|---|---|---|
| `resident@goldenhomes.local` | `12-4-A` | `ABC 1234` (Car, White), `XYZ 7788` (Motorcycle, Black) |
| `resident2@goldenhomes.local` | `15-7-B` | `DEF 2468` (Car, Silver), `GHI 1357` (Motorcycle, Blue) |
| `resident3@goldenhomes.local` | `3-9-C` | `RES3 1001` (Car, Pearl White), `RES3 1002` (Motorcycle, Matte Black), `RES3 1003` (Car, Ocean Blue) |
| `resident4@goldenhomes.local` | `8-2-D` | `RES4 2001` (Car, Ruby Red) |
| `resident5@goldenhomes.local` | `20-11-E` | `RES5 3001` (Car, Midnight Blue), `RES5 3002` (Motorcycle, Graphite Gray), `RES5 3003` (Car, Forest Green), `RES5 3004` (Car, Champagne Gold), `RES5 3005` (Motorcycle, Pearl Silver) |

### Staff

| Account | Role | Vehicles |
|---|---|---|
| `guard@goldenhomes.local` | Guard | `GRD 1001` (Car, Black), `GRD 2002` (Motorcycle, Red) |
| `guard2@goldenhomes.local` | Guard | `GRD2 1001` (Car, Navy Blue) |
| `guard3@goldenhomes.local` | Guard | `GRD3 1001` (Motorcycle, Sunset Orange), `GRD3 1002` (Car, Pearl White) |
| `admin@goldenhomes.local` | Admin | `ADM 3003` (Car, White), `ADM 4004` (Motorcycle, Gray) |
| `admin2@goldenhomes.local` | Admin | `ADM2 1001` (Car, Steel Gray), `ADM2 1002` (Motorcycle, Deep Red) |


## ESP32 Endpoint
POST to:
- `/smart-gate/api/esp32/log_access.php`

Accepted fields:
- `rfid_uid`
- `plate_number`
- `event_type`
- `source_device`
- `manual_override`
- `plate_photo` (file)
- `vehicle_photo` (file)


<!-- BISM4RCK/KUN3H0 2026 -->

<!-- BISM4RCK-KUN3H0 2026 -->
