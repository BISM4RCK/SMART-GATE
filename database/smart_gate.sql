CREATE DATABASE IF NOT EXISTS smart_gate
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_gate;

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
    house_number VARCHAR(50) NOT NULL,
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
    plate_number VARCHAR(30) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle', 'truck', 'other') NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    color VARCHAR(50) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_plate_number (plate_number),
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
    visitor_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(30) NULL,
    plate_number VARCHAR(30) NULL,
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
    booking_id BIGINT UNSIGNED NULL,
    resident_id BIGINT UNSIGNED NULL,
    guard_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    event_type ENUM('entry', 'exit', 'manual_open', 'qr_scan', 'rfid_scan', 'walk_in') NOT NULL,
    person_name VARCHAR(150) NULL,
    plate_number VARCHAR(30) NULL,
    qr_reference VARCHAR(120) NULL,
    rfid_uid VARCHAR(100) NULL,
    gate_status ENUM('approved', 'denied', 'pending', 'manual_override') NOT NULL DEFAULT 'pending',
    log_notes VARCHAR(255) NULL,
    captured_image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gate_logs_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_guard FOREIGN KEY (guard_id) REFERENCES guards(id) ON DELETE SET NULL,
    CONSTRAINT fk_gate_logs_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
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

CREATE INDEX idx_visitors_status ON visitor_requests(status);
CREATE INDEX idx_visitors_resident ON visitor_requests(resident_id);
CREATE INDEX idx_gate_logs_created_at ON gate_logs(created_at);
CREATE INDEX idx_gate_logs_plate ON gate_logs(plate_number);
CREATE INDEX idx_blacklist_plate ON blacklist(plate_number);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);


-- SAMPLE SEED DATA (starter accounts)
INSERT INTO users (full_name, email, password, role, status) VALUES
('Sample Resident', 'resident@example.com', '$2y$12$dqjlvO6.sPxX/AhZ//x.ue4oZbXMKMExWVfVnVWny90oMz7rFwByO', 'resident', 'active'),
('Sample Guard', 'guard@example.com', '$2y$12$dqjlvO6.sPxX/AhZ//x.ue4oZbXMKMExWVfVnVWny90oMz7rFwByO', 'guard', 'active'),
('Sample Admin', 'admin@example.com', '$2y$12$dqjlvO6.sPxX/AhZ//x.ue4oZbXMKMExWVfVnVWny90oMz7rFwByO', 'admin', 'active');

INSERT INTO residents (user_id, house_number, block_number, contact_number, emergency_contact, status)
SELECT id, '12', 'A', '09170000001', '09170000002', 'active'
FROM users WHERE email = 'resident@example.com';

INSERT INTO guards (user_id, guard_code, shift_name, contact_number, status)
SELECT id, 'GRD-001', 'Day Shift', '09170000003', 'active'
FROM users WHERE email = 'guard@example.com';

INSERT INTO admins (user_id, admin_code, status)
SELECT id, 'ADM-001', 'active'
FROM users WHERE email = 'admin@example.com';
