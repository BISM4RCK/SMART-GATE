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
## Feature upgrade in this build

- Residents can add and remove their own vehicles.
- Admins can add and remove vehicles for any resident.
- Admins can create and remove resident, guard, and admin accounts. The currently signed-in admin cannot remove their own account.
- Notification "Mark as read" and "Mark all as read" actions are functional.
- Admins can delete tickets; residents cannot delete tickets.
- Admins and guards can add and remove blacklist entries.
- Destructive forms use session CSRF tokens.

BISM4RCK/KUN3H0 2026


### Notifications
- Users can delete their own notifications from the notification interface.
