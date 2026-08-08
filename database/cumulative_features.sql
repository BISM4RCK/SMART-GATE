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
