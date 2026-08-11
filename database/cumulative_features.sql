-- GOLDEN HOMES cumulative features migration
-- README intentionally untouched.
ALTER TABLE residents ADD COLUMN IF NOT EXISTS lot_number VARCHAR(50) NULL AFTER block_number;
ALTER TABLE residents ADD COLUMN IF NOT EXISTS household_letter VARCHAR(5) NULL AFTER lot_number;

CREATE TABLE IF NOT EXISTS visitor_credentials(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, visitor_request_id BIGINT UNSIGNED NOT NULL, visitor_id CHAR(6) NOT NULL,
 qr_token_hash CHAR(64) NOT NULL, barcode_token_hash CHAR(64) NOT NULL, qr_token VARCHAR(255) NOT NULL, barcode_token VARCHAR(255) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_vid(visitor_id), UNIQUE KEY uq_vr(visitor_request_id), UNIQUE KEY uq_qr(qr_token_hash), UNIQUE KEY uq_barcode(barcode_token_hash),
 CONSTRAINT fk_vc_request FOREIGN KEY(visitor_request_id) REFERENCES visitor_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE visitor_credentials ADD COLUMN IF NOT EXISTS qr_token VARCHAR(255) NULL;
ALTER TABLE visitor_credentials ADD COLUMN IF NOT EXISTS barcode_token VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS account_activity_logs(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, account_type VARCHAR(20) NOT NULL, account_identifier VARCHAR(120) NULL, action VARCHAR(80) NOT NULL, details TEXT NULL, ip_address VARCHAR(45) NULL, user_agent TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_type(account_type),KEY idx_user(user_id),KEY idx_action(action),KEY idx_created(created_at), CONSTRAINT fk_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE account_activity_logs ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL;
ALTER TABLE account_activity_logs ADD COLUMN IF NOT EXISTS user_agent TEXT NULL;

CREATE TABLE IF NOT EXISTS gate_commands(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, issued_by BIGINT UNSIGNED NULL, issued_by_role VARCHAR(20) NULL, command VARCHAR(40) NOT NULL, source VARCHAR(40) NOT NULL, payload JSON NULL,status ENUM('pending','completed','denied','expired') NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,completed_at DATETIME NULL,
 KEY idx_status(status),KEY idx_created(created_at), CONSTRAINT fk_gate_command_user FOREIGN KEY(issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_vehicles(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL, plate_number VARCHAR(32) NOT NULL,vehicle_type VARCHAR(50) NULL,color VARCHAR(50) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_user(user_id),KEY idx_plate(plate_number), CONSTRAINT fk_user_vehicle_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE user_vehicles ADD COLUMN IF NOT EXISTS color VARCHAR(50) NULL;

-- Demo accounts and vehicles for gate testing.
INSERT INTO users (full_name,email,password,role,status)
SELECT 'Golden Resident Two','resident2@goldenhomes.local',password,'resident','active' FROM users WHERE email='resident@goldenhomes.local' AND NOT EXISTS (SELECT 1 FROM users WHERE email='resident2@goldenhomes.local') LIMIT 1;
SET @resident2_user_id=(SELECT id FROM users WHERE email='resident2@goldenhomes.local' LIMIT 1);
INSERT INTO residents (user_id,house_number,block_number,lot_number,household_letter,contact_number)
SELECT @resident2_user_id,'15-7-B','15','7','B','09171234568' WHERE @resident2_user_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM residents WHERE user_id=@resident2_user_id);
SET @resident2_id=(SELECT id FROM residents WHERE user_id=@resident2_user_id LIMIT 1);
INSERT INTO vehicles (resident_id,plate_number,vehicle_type,brand,model,color)
SELECT @resident2_id,'DEF 2468','car','Mitsubishi','Mirage','Silver' WHERE @resident2_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM vehicles WHERE plate_number='DEF 2468');
INSERT INTO vehicles (resident_id,plate_number,vehicle_type,brand,model,color)
SELECT @resident2_id,'GHI 1357','motorcycle','Yamaha','Mio','Blue' WHERE @resident2_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM vehicles WHERE plate_number='GHI 1357');
INSERT INTO user_vehicles (user_id,plate_number,vehicle_type,color) SELECT id,'GRD 1001','car','Black' FROM users WHERE email='guard@goldenhomes.local' AND NOT EXISTS (SELECT 1 FROM user_vehicles WHERE plate_number='GRD 1001');
INSERT INTO user_vehicles (user_id,plate_number,vehicle_type,color) SELECT id,'GRD 2002','motorcycle','Red' FROM users WHERE email='guard@goldenhomes.local' AND NOT EXISTS (SELECT 1 FROM user_vehicles WHERE plate_number='GRD 2002');
INSERT INTO user_vehicles (user_id,plate_number,vehicle_type,color) SELECT id,'ADM 3003','car','White' FROM users WHERE email='admin@goldenhomes.local' AND NOT EXISTS (SELECT 1 FROM user_vehicles WHERE plate_number='ADM 3003');
INSERT INTO user_vehicles (user_id,plate_number,vehicle_type,color) SELECT id,'ADM 4004','motorcycle','Gray' FROM users WHERE email='admin@goldenhomes.local' AND NOT EXISTS (SELECT 1 FROM user_vehicles WHERE plate_number='ADM 4004');


-- BISM4RCK-KUN3H0 2026
ALTER TABLE residents MODIFY COLUMN house_number VARCHAR(50) NOT NULL;
ALTER TABLE gate_logs ADD COLUMN IF NOT EXISTS walk_in_id BIGINT UNSIGNED NULL AFTER visitor_request_id;
ALTER TABLE gate_logs ADD COLUMN IF NOT EXISTS actor_user_id BIGINT UNSIGNED NULL AFTER guard_id;
ALTER TABLE gate_logs ADD COLUMN IF NOT EXISTS actor_role VARCHAR(20) NULL AFTER actor_user_id;
ALTER TABLE gate_logs MODIFY COLUMN event_type VARCHAR(50) NOT NULL;
CREATE TABLE IF NOT EXISTS walk_in_visitors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 visitor_id CHAR(6) NOT NULL,
 visitor_name VARCHAR(150) NOT NULL,
 contact_number VARCHAR(30) NULL,
 purpose_of_visit VARCHAR(255) NOT NULL,
 plate_number VARCHAR(30) NULL,
 vehicle_type VARCHAR(50) NOT NULL DEFAULT 'other',
 barcode_token_hash CHAR(64) NOT NULL,
 barcode_token VARCHAR(255) NOT NULL,
 created_by BIGINT UNSIGNED NULL,
 status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_walkin_visitor_id(visitor_id), UNIQUE KEY uq_walkin_barcode(barcode_token_hash),
 KEY idx_walkin_created(created_at), KEY idx_walkin_created_by(created_by),
 CONSTRAINT fk_walkin_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS walk_in_visitor_vehicles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 walk_in_id BIGINT UNSIGNED NOT NULL,
 plate_number VARCHAR(30) NOT NULL,
 vehicle_type VARCHAR(50) NOT NULL DEFAULT 'other',
 people_count INT UNSIGNED NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 KEY idx_walkin_vehicle(walk_in_id),
 CONSTRAINT fk_walkin_vehicle_walkin FOREIGN KEY(walk_in_id) REFERENCES walk_in_visitors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- RFID account profiles for resident/staff credentials.
CREATE TABLE IF NOT EXISTS rfid_cards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 vehicle_id BIGINT UNSIGNED NULL,
 uid VARCHAR(100) NULL,
 credential_code VARCHAR(100) NULL,
 status ENUM('active','void') NOT NULL DEFAULT 'active',
 issued_by BIGINT UNSIGNED NULL,
 issued_at DATETIME NULL,
 voided_by BIGINT UNSIGNED NULL,
 voided_at DATETIME NULL,
 notes VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_rfid_uid(uid),
 KEY idx_rfid_user(user_id), KEY idx_rfid_vehicle(vehicle_id), KEY idx_rfid_status(status),
 CONSTRAINT fk_rfid_card_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_rfid_card_vehicle FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
 CONSTRAINT fk_rfid_card_issued_by FOREIGN KEY(issued_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_rfid_card_voided_by FOREIGN KEY(voided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rfid_scan_sessions (
    id CHAR(32) PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    actor_role ENUM('guard','admin') NOT NULL,
    purpose ENUM('gate','burn') NOT NULL DEFAULT 'gate',
    target_user_id BIGINT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    status ENUM('waiting','submitted','approved','error','expired') NOT NULL DEFAULT 'waiting',
    rfid_uid VARCHAR(100) NULL,
    result_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    KEY idx_rfid_scan_device_status(device_id,status,expires_at),
    KEY idx_rfid_scan_target(target_user_id), KEY idx_rfid_scan_vehicle(target_vehicle_id),
    CONSTRAINT fk_rfid_scan_target FOREIGN KEY(target_user_id) REFERENCES users(id) ON DELETE SET NULL, CONSTRAINT fk_rfid_scan_vehicle FOREIGN KEY(target_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    CONSTRAINT fk_rfid_scan_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Continuous RFID access and ESP32 gate-control updates for existing Smart Gate installations.
ALTER TABLE rfid_cards ADD COLUMN IF NOT EXISTS credential_code VARCHAR(100) NULL;
CREATE TABLE IF NOT EXISTS rfid_scan_sessions (
 id CHAR(32) PRIMARY KEY, device_id VARCHAR(64) NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, actor_role ENUM('guard','admin') NOT NULL, purpose ENUM('gate','burn') NOT NULL DEFAULT 'gate', target_user_id BIGINT UNSIGNED NULL, target_vehicle_id BIGINT UNSIGNED NULL, notes VARCHAR(255) NULL, status ENUM('waiting','submitted','approved','error','expired') NOT NULL DEFAULT 'waiting', rfid_uid VARCHAR(100) NULL, result_json LONGTEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, expires_at DATETIME NOT NULL, KEY idx_rfid_scan_device_status(device_id,status,expires_at), KEY idx_rfid_scan_target(target_user_id), KEY idx_rfid_scan_vehicle(target_vehicle_id), CONSTRAINT fk_rfid_scan_target FOREIGN KEY(target_user_id) REFERENCES users(id) ON DELETE SET NULL, CONSTRAINT fk_rfid_scan_vehicle FOREIGN KEY(target_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL, CONSTRAINT fk_rfid_scan_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RFID resident vehicle association.
ALTER TABLE rfid_cards ADD COLUMN IF NOT EXISTS vehicle_id BIGINT UNSIGNED NULL;
ALTER TABLE rfid_scan_sessions ADD COLUMN IF NOT EXISTS target_vehicle_id BIGINT UNSIGNED NULL;
