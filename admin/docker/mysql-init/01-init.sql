-- DOTK Admin Database Initialization Script
-- This script runs automatically when MySQL container starts for the first time

-- Ensure database exists and use it
USE dotk_admin;

-- Create users table with authentication support
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'editor') NOT NULL DEFAULT 'editor',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default super admin user (password: admin123)
-- Password hash: $2y$10$CVJpXYm0KweK8jLvLmLgUeUxzxOLOBa23pzVHGkJH197k23P5e/Hy
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES 
('admin', 'admin@dotk.gov.in', '$2y$10$CVJpXYm0KweK8jLvLmLgUeUxzxOLOBa23pzVHGkJH197k23P5e/Hy', 'Super Administrator', 'super_admin', 1)
ON DUPLICATE KEY UPDATE username=username;

-- Create activity_logs table for audit trail
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant necessary privileges (already done in docker-compose, but good to be explicit)
GRANT ALL PRIVILEGES ON dotk_admin.* TO 'dotk_admin_user'@'%';
FLUSH PRIVILEGES;
