-- Create database with proper charset/collation
CREATE DATABASE IF NOT EXISTS idvault_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE idvault_db;

-- Users table (FIXED: Removed redundant salt column, added token fields)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,  -- Salt is included in hash (password_hash() handles this)
    role ENUM('user', 'admin') DEFAULT 'user' NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE NOT NULL,
    verification_token VARCHAR(64) NULL,  -- For email verification
    token_expires_at DATETIME NULL,       -- Token expiry
    verification_status ENUM('unverified', 'pending', 'verified') DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Password reset tokens (FIXED: Added UNIQUE constraint to token)
CREATE TABLE password_reset_tokens (  -- Fixed typo in table name (reset, not resET)
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,  -- Ensure tokens are unique
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- User documents table (FIXED: Consistent naming for document_type)
CREATE TABLE user_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('affidavit', 'proof_of_address', 'passport', 'visa', 'study_permit', 'other') NOT NULL,  -- Added 'other'
    document_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending' NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,  -- CASCADE on delete
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Document approval history (FIXED: Added action_details for clarity)
CREATE TABLE document_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    action ENUM('submitted', 'review_started', 'approved', 'rejected', 'downloaded', 'comment_added') NOT NULL,  -- Added 'comment_added'
    action_details TEXT NULL,  -- Additional context for actions
    performed_by INT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES user_documents(document_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_document (document_id),
    INDEX idx_action (action)
) ENGINE=InnoDB;

-- Admin activity log (FIXED: Added action_type for categorization)
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('login', 'user_modified', 'document_review', 'system_change') NOT NULL,  -- Categorize actions
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,  -- CASCADE if admin is deleted
    INDEX idx_admin (admin_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Identity verifications table (FIXED: Added document_type for clarity)
CREATE TABLE identity_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    id_document_type ENUM('passport', 'national_id', 'drivers_license') NOT NULL,  -- Specify type
    id_document_path VARCHAR(255) NOT NULL,
    student_document_type ENUM('acceptance_letter', 'enrollment_proof') NOT NULL,
    student_document_path VARCHAR(255) NOT NULL,
    address_document_type ENUM('utility_bill', 'bank_statement') NOT NULL,
    address_document_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewer_id INT NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Initial admin account (FIXED: Removed salt, updated password hash)
INSERT INTO users (full_name, email, phone, password_hash, role, is_verified)
VALUES (
    'System Admin', 
    'admin@idvault.com', 
    '+1234567890', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Hash for "Admin@1234"
    'admin', 
    TRUE
);