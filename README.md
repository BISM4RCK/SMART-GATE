# GOLDEN HOMES Subdivision

A minimalist Bootstrap 5 local-hosted web application for gated communities.

## Features
- Landing page with just two choices: **Visitor Request** or **Login**
- No role selection on the login page
- Account-based dashboard routing
- Visitor request by **house number** instead of resident name
- Simple resident dashboard
- Simple guard dashboard
- Simple admin dashboard
- Resident-admin concerns inbox
- Local MySQL database
- Ready for later ESP32, RFID, and gate integration
- API folder and config folder included for future expansion

## Technologies Used
- HTML
- CSS
- JavaScript
- PHP
- MySQL
- Bootstrap 5
- XAMPP / Apache
- HTTP / MQTT (later hardware integration)

## Project Structure
```text
smart-gate/
├── admin/
├── api/
├── assets/
├── config/
├── database/
├── guard/
├── includes/
├── resident/
├── uploads/
└── index.php
```

## Setup Instructions
1. Copy the folder into `C:\xampp\htdocs\smart-gate`
2. Start Apache and MySQL in XAMPP
3. Create a database named `smart_gate`
4. Import `database/smart_gate.sql`
5. Open `http://localhost/smart-gate/`

## Demo Accounts
- resident@goldenhomes.local
- guard@goldenhomes.local
- admin@goldenhomes.local

Password for all demo accounts:
- `Password123!`

## Notes
- The logo sends logged-in users back to their dashboard.
- Visitors enter house number directly to avoid exposing resident names in a dropdown.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors
BISM4RCK/KUN3H0
