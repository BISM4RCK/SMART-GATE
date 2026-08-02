# GOLDEN HOMES Subdivision

A lightweight MVC PHP system for a gated community, built for XAMPP and MySQL.

## Setup
1. Put the folder in `C:\xampp\htdocs\smart-gate`
2. Start Apache and MySQL in XAMPP
3. Import `database/smart_gate.sql` in phpMyAdmin
4. Open `http://YOUR-PC-IP/smart-gate/` or `http://localhost/smart-gate/`

## Demo Accounts
- resident@goldenhomes.local
- guard@goldenhomes.local
- admin@goldenhomes.local

Password:
- `Password123!`

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

## BISM4RCK/KUN3H0 2026

<!-- BISM4RCK/KUN3H0 2026 -->

