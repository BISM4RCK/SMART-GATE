CREATE DATABASE IF NOT EXISTS smart_gate;
USE smart_gate;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS access_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS visitor_bookings;
DROP TABLE IF EXISTS visitor_ids;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS rfid_tags;
DROP TABLE IF EXISTS cameras;
DROP TABLE IF EXISTS gates;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS=1;





CREATE TABLE users(

id INT AUTO_INCREMENT PRIMARY KEY,

fullname VARCHAR(100) NOT NULL,

username VARCHAR(50) UNIQUE NOT NULL,

email VARCHAR(100) UNIQUE,

password VARCHAR(255) NOT NULL,

phone VARCHAR(30),

house_block VARCHAR(20),

house_lot VARCHAR(20),

role ENUM(
'admin',
'guard',
'resident'
) DEFAULT 'resident',

status ENUM(
'active',
'inactive',
'banned'
) DEFAULT 'active',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);





CREATE TABLE vehicles(

id INT AUTO_INCREMENT PRIMARY KEY,

resident_id INT NOT NULL,

plate_number VARCHAR(20) UNIQUE,

vehicle_type VARCHAR(50),

brand VARCHAR(50),

model VARCHAR(50),

color VARCHAR(40),

year_model INT,

status ENUM(
'active',
'disabled'
) DEFAULT 'active',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(resident_id)
REFERENCES users(id)
ON DELETE CASCADE

);





CREATE TABLE rfid_tags(

id INT AUTO_INCREMENT PRIMARY KEY,

vehicle_id INT NOT NULL,

rfid_uid VARCHAR(100) UNIQUE,

tag_type ENUM(
'card',
'sticker'
) DEFAULT 'sticker',

assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

status ENUM(
'active',
'lost',
'disabled'
) DEFAULT 'active',

FOREIGN KEY(vehicle_id)
REFERENCES vehicles(id)
ON DELETE CASCADE

);





CREATE TABLE visitor_bookings(

id INT AUTO_INCREMENT PRIMARY KEY,

resident_id INT NOT NULL,

visitor_name VARCHAR(120),

visitor_phone VARCHAR(30),

visitor_plate VARCHAR(20),

vehicle_type VARCHAR(40),

purpose TEXT,

visit_date DATE,

arrival_time TIME,

departure_time TIME,

qr_code VARCHAR(255),

approval ENUM(
'pending',
'approved',
'rejected',
'expired'
) DEFAULT 'pending',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(resident_id)
REFERENCES users(id)
ON DELETE CASCADE

);





CREATE TABLE visitor_ids(

id INT AUTO_INCREMENT PRIMARY KEY,

booking_id INT,

id_type VARCHAR(50),

id_number VARCHAR(80),

photo VARCHAR(255),

FOREIGN KEY(booking_id)
REFERENCES visitor_bookings(id)
ON DELETE CASCADE

);





CREATE TABLE gates(

id INT AUTO_INCREMENT PRIMARY KEY,

gate_name VARCHAR(60),

gate_location VARCHAR(100),

gate_type ENUM(
'entry',
'exit'
),

ip_address VARCHAR(50),

status ENUM(
'online',
'offline',
'maintenance'
) DEFAULT 'online'

);





CREATE TABLE cameras(

id INT AUTO_INCREMENT PRIMARY KEY,

gate_id INT,

camera_name VARCHAR(60),

camera_ip VARCHAR(50),

resolution VARCHAR(20),

status ENUM(
'online',
'offline'
) DEFAULT 'online',

FOREIGN KEY(gate_id)
REFERENCES gates(id)
ON DELETE CASCADE

);





CREATE TABLE access_logs(

id BIGINT AUTO_INCREMENT PRIMARY KEY,

gate_id INT,

vehicle_id INT,

booking_id INT,

plate_number VARCHAR(20),

rfid_uid VARCHAR(100),

access_type ENUM(
'resident',
'visitor'
),

direction ENUM(
'entry',
'exit'
),

verification ENUM(
'rfid',
'plate',
'qr',
'manual'
),

snapshot VARCHAR(255),

remarks TEXT,

access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(vehicle_id)
REFERENCES vehicles(id)
ON DELETE SET NULL,

FOREIGN KEY(booking_id)
REFERENCES visitor_bookings(id)
ON DELETE SET NULL,

FOREIGN KEY(gate_id)
REFERENCES gates(id)
ON DELETE SET NULL

);





CREATE TABLE notifications(

id INT AUTO_INCREMENT PRIMARY KEY,

user_id INT,

title VARCHAR(120),

message TEXT,

is_read TINYINT(1) DEFAULT 0,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(user_id)
REFERENCES users(id)
ON DELETE CASCADE

);





INSERT INTO users(

fullname,
username,
email,
password,
phone,
house_block,
house_lot,
role

)

VALUES(

'Administrator',

'admin',

'admin@smartgate.local',

'$2y$10$8k1pT9k4sN6Qj3n9mA4A1uJmW9qJ0qzQHq4zS6wF5f0HkA1N6vP3K',

'09170000000',

'Admin',

'Office',

'admin'

);





INSERT INTO users(

fullname,
username,
email,
password,
phone,
house_block,
house_lot,
role

)

VALUES(

'Juan Dela Cruz',

'juan',

'juan@gmail.com',

'$2y$10$8k1pT9k4sN6Qj3n9mA4A1uJmW9qJ0qzQHq4zS6wF5f0HkA1N6vP3K',

'09181234567',

'5',

'18',

'resident'

);





INSERT INTO vehicles(

resident_id,

plate_number,

vehicle_type,

brand,

model,

color,

year_model

)

VALUES(

2,

'NAA1234',

'SUV',

'Toyota',

'Fortuner',

'Black',

2024

);





INSERT INTO rfid_tags(

vehicle_id,

rfid_uid

)

VALUES(

1,

'RFID-000001'

);





INSERT INTO gates(

gate_name,

gate_location,

gate_type,

ip_address

)

VALUES(

'Main Entrance',

'North Gate',

'entry',

'192.168.1.10'

),

(

'Main Exit',

'South Gate',

'exit',

'192.168.1.11'

);





INSERT INTO cameras(

gate_id,

camera_name,

camera_ip,

resolution

)

VALUES(

1,

'Entrance Camera',

'192.168.1.20',

'1920x1080'

),

(

2,

'Exit Camera',

'192.168.1.21',

'1920x1080'

);