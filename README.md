# Smart Visitor and Resident Management System

A locally hosted web application for gated communities that helps residents pre-register visitors, allows guards to verify entries, and gives administrators a centralized way to manage records, logs, and access requests.

## Features

- Landing page with **Resident Login** and **Visitor Registration**
- Resident authentication
- Visitor request form with ID upload
- Resident approval or rejection of visitor requests
- Guard dashboard for verification and monitoring
- Admin dashboard for management and reporting
- Local MySQL database storage
- Ready for future integration with ESP32, RFID, and gate hardware

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP / Apache
- **Hosting:** Local Area Network (LAN)
- **Future Hardware Integration:** ESP32
- **Communication:** HTTP / MQTT

## Project Structure

```text
smart_gate/
├── admin/
├── resident/
├── guard/
├── visitor/
├── api/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── database/
├── includes/
├── uploads/
├── logs/
└── index.php
```

## Requirements

### Software
- XAMPP
- PHP 8.x
- MySQL
- Apache
- Web browser
- Git

### Optional
- phpMyAdmin
- Visual Studio Code

## Setup Instructions

### 1. Clone or download the project
Place the project folder inside your XAMPP `htdocs` directory.

```text
C:\xampp\htdocs\smart_gate\
```

### 2. Start XAMPP
Open XAMPP Control Panel and start:
- Apache
- MySQL

### 3. Create the database
Open phpMyAdmin and create a database named:

```text
smart_gate
```

### 4. Import the SQL schema
Import the database file from:

```text
database/smart_gate.sql
```

### 5. Configure the database connection
Update your PHP configuration file if needed:

```php
$host = "localhost";
$dbname = "smart_gate";
$username = "root";
$password = "";
```

### 6. Open the application
Open your browser and go to:

```text
http://localhost/smart_gate/
```

If you want to access it from a phone on the same Wi-Fi network, use your computer’s local IP address instead of `localhost`.

## Default Access Flow

1. User opens the landing page
2. Chooses either:
   - Resident Login
   - Visitor Registration
3. Visitor submits details and uploads an ID
4. Resident reviews the request
5. Resident approves or rejects the visitor
6. Guard checks the approval record at the gate

## Notes

- This project is designed to run locally using XAMPP.
- Internet access is not required for core functionality.
- Uploaded files such as government IDs should be handled carefully for privacy and security.
- This is a capstone prototype and can be expanded later with RFID reader, QR scanner, ESP32 gate control, and license plate recognition.

## Security Reminders

- Do not commit real passwords or secrets to GitHub.
- Use a `.gitignore` file to exclude sensitive and generated files.
- Protect uploaded ID images and other private data.
- Use hashed passwords and role-based access control.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors

BISM4RCK/KUN3H0
