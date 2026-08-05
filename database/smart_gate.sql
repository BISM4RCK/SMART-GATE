-- BISM4RCK/KUN3H0 2026
-- BISM4RCK/KUN3H0 2026
CREATE DATABASE IF NOT EXISTS smart_gate
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_gate;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS concerns;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS gate_logs;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS visitor_request_vehicles;
DROP TABLE IF EXISTS visitor_attachments;
DROP TABLE IF EXISTS visitor_requests;
DROP TABLE IF EXISTS blacklist;
DROP TABLE IF EXISTS rfid_tags;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS guards;
DROP TABLE IF EXISTS residents;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('resident', 'guard', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE residents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    house_number VARCHAR(50) NOT NULL UNIQUE,
    block_number VARCHAR(50) NULL,
    contact_number VARCHAR(30) NULL,
    emergency_contact VARCHAR(30) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_residents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE guards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    guard_code VARCHAR(50) NULL,
    shift_name VARCHAR(50) NULL,
    contact_number VARCHAR(30) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_guards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    admin_code VARCHAR(50) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id BIGINT UNSIGNED NOT NULL,
    plate_number VARCHAR(30) NOT NULL UNIQUE,
    vehicle_type ENUM('car', 'motorcycle', 'truck', 'other') NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    color VARCHAR(50) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rfid_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id BIGINT UNSIGNED NOT NULL UNIQUE,
    uid VARCHAR(100) NOT NULL UNIQUE,
    tag_type ENUM('windshield', 'card', 'fob', 'other') NOT NULL DEFAULT 'windshield',
    issued_at DATETIME NULL,
    status ENUM('active', 'inactive', 'lost', 'revoked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rfid_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE visitor_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id BIGINT UNSIGNED NOT NULL,
    house_number VARCHAR(50) NOT NULL,
    visitor_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(30) NULL,
    plate_number VARCHAR(30) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle', 'truck', 'other') NOT NULL DEFAULT 'other',
    purpose_of_visit VARCHAR(255) NOT NULL,
    government_id_type VARCHAR(100) NULL,
    government_id_number VARCHAR(100) NULL,
    status ENUM('pending', 'approved', 'rejected', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    requested_visit_date DATE NULL,
    requested_arrival_time TIME NULL,
    requested_departure_time TIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    rejected_by BIGINT UNSIGNED NULL,
    rejected_at DATETIME NULL,
    rejection_reason VARCHAR(255) NULL,
    qr_reference VARCHAR(120) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_visitor_requests_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    CONSTRAINT fk_visitor_requests_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_visitor_requests_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE visitor_request_vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_request_id BIGINT UNSIGNED NOT NULL,
    plate_number VARCHAR(30) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle', 'truck', 'other') NOT NULL DEFAULT 'other',
    people_count INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vrv_request FOREIGN KEY (visitor_request_id) REFERENCES visitor_requests(id) ON DELETE CASCADE,
    INDEX idx_vrv_request (visitor_request_id)
) ENGINE=InnoDB;

CREATE TABLE visitor_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_request_id BIGINT UNSIGNED NOT NULL,
    file_type ENUM('government_id', 'other') NOT NULL DEFAULT 'government_id',
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NULL,
    mime_type VARCHAR(100) NULL,
    file_size BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachments_request FOREIGN KEY (visitor_request_id) REFERENCES visitor_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_request_id BIGINT UNSIGNED NOT NULL UNIQUE,
    booking_code VARCHAR(100) NOT NULL UNIQUE,
    qr_code_text VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    check_in_at DATETIME NULL,
    check_out_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_request FOREIGN KEY (visitor_request_id) REFERENCES visitor_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gate_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    visitor_request_id BIGINT UNSIGNED NULL,
    guard_id BIGINT UNSIGNED NULL,
    rfid_uid VARCHAR(100) NULL,
    plate_number VARCHAR(30) NULL,
    event_type ENUM('entry', 'exit', 'manual_open', 'rfid_scan', 'plate_scan', 'combined_scan') NOT NULL,
    gate_status ENUM('approved', 'denied', 'pending', 'manual_override') NOT NULL DEFAULT 'pending',
    source_device VARCHAR(100) NULL,
    plate_photo_path VARCHAR(255) NULL,
    vehicle_photo_path VARCHAR(255) NULL,
    raw_payload LONGTEXT NULL,
    log_notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gate_logs_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_guard FOREIGN KEY (guard_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_visitor_request FOREIGN KEY (visitor_request_id) REFERENCES visitor_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    module_name VARCHAR(100) NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE blacklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id BIGINT UNSIGNED NULL,
    visitor_name VARCHAR(150) NULL,
    plate_number VARCHAR(30) NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_blacklist_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
    CONSTRAINT fk_blacklist_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE concerns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id BIGINT UNSIGNED NULL,
    sender_name VARCHAR(150) NOT NULL,
    sender_role ENUM('resident', 'admin') NOT NULL DEFAULT 'resident',
    house_number VARCHAR(50) NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    reply TEXT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    replied_by BIGINT UNSIGNED NULL,
    replied_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_concerns_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
    CONSTRAINT fk_concerns_replied_by FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_residents_house_number ON residents(house_number);
CREATE INDEX idx_visitor_requests_house_number ON visitor_requests(house_number);
CREATE INDEX idx_visitor_requests_status ON visitor_requests(status);
CREATE INDEX idx_gate_logs_created_at ON gate_logs(created_at);
CREATE INDEX idx_gate_logs_plate ON gate_logs(plate_number);
CREATE INDEX idx_blacklist_plate ON blacklist(plate_number);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_concerns_status ON concerns(status);

INSERT INTO users (full_name, email, password, role, status) VALUES
('Golden Resident', 'resident@goldenhomes.local', '$2y$12$8FfPm6d09mvevtK3as10UeLq3X.EX0Xv05vsgTX0kIg5POU7XuE/6', 'resident', 'active'),
('Gate Guard', 'guard@goldenhomes.local', '$2y$12$8YEEawRXeKk19/k1fwZMtewlsjvOZN4x0VgvfOt5YEtcJWOeG3PAW', 'guard', 'active'),
('Subdivision Admin', 'admin@goldenhomes.local', '$2y$12$HiPq2.RM1PGErw7L0t7lNuGwUzh5ESj5XFWt.UWe0QG4VEeJe6Mzi', 'admin', 'active');

SET @resident_user_id = (SELECT id FROM users WHERE email='resident@goldenhomes.local' LIMIT 1);
SET @guard_user_id = (SELECT id FROM users WHERE email='guard@goldenhomes.local' LIMIT 1);
SET @admin_user_id = (SELECT id FROM users WHERE email='admin@goldenhomes.local' LIMIT 1);

INSERT INTO residents (user_id, house_number, block_number, contact_number, emergency_contact)
VALUES (@resident_user_id, '12-A', 'Block 12', '09171234567', '09179876543');

INSERT INTO guards (user_id, guard_code, shift_name, contact_number)
VALUES (@guard_user_id, 'GRD-001', 'Day Shift', '09170001111');

INSERT INTO admins (user_id, admin_code)
VALUES (@admin_user_id, 'ADM-001');

SET @resident_id = (SELECT id FROM residents WHERE house_number='12-A' LIMIT 1);

INSERT INTO vehicles (resident_id, plate_number, vehicle_type, brand, model, color) VALUES
(@resident_id, 'ABC 1234', 'car', 'Toyota', 'Vios', 'White'),
(@resident_id, 'XYZ 7788', 'motorcycle', 'Honda', 'Click', 'Black');

INSERT INTO rfid_tags (vehicle_id, uid, tag_type, issued_at, status)
VALUES ((SELECT id FROM vehicles WHERE plate_number='ABC 1234' LIMIT 1), 'RFID-ABC1234', 'windshield', NOW(), 'active');

INSERT INTO visitor_requests (
    resident_id, house_number, visitor_name, contact_number, plate_number,
    vehicle_type, purpose_of_visit, status, requested_visit_date, requested_arrival_time, qr_reference
) VALUES
(@resident_id, '12-A', 'Daniel Cruz', '09181112222', 'KBA 9090', 'car', 'Family visit', 'pending', CURDATE(), '14:00:00', 'GH-REQ-0001'),
(@resident_id, '12-A', 'Mika Santos', '09183334444', 'NQZ 2211', 'motorcycle', 'Delivery', 'approved', CURDATE(), '15:00:00', 'GH-REQ-0002');

INSERT INTO concerns (resident_id, sender_name, sender_role, house_number, subject, message, status)
VALUES (@resident_id, 'Golden Resident', 'resident', '12-A', 'Streetlight is flickering', 'The streetlight near our gate has been flickering since last night.', 'open');

INSERT INTO notifications (user_id, title, message, is_read)
VALUES
(@resident_user_id, 'Visitor pending', 'You have a pending visitor request for House 12-A.', 0),
(@admin_user_id, 'New concern', 'A resident submitted a new concern.', 0),
(@guard_user_id, 'Guard notice', 'Your dashboard is ready.', 0);
-- BISM4RCK/KUN3H0 2026
