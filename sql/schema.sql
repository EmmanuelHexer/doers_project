-- ============================================
-- USTED-K GYM CENTER MANAGEMENT SYSTEM
-- Database: gym_database
-- Complete Schema with All Tables
-- ============================================

-- Drop database if exists (for fresh install)
DROP DATABASE IF EXISTS gym_database;
CREATE DATABASE gym_database;
USE gym_database;

-- ============================================
-- 1. MEMBERSHIP TYPE TABLE
-- ============================================
CREATE TABLE Membership_Type (
    membership_type_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    fee DECIMAL(10,2) NOT NULL,
    duration_months INT NOT NULL,
    benefits TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. INSTRUCTOR TABLE
-- ============================================
CREATE TABLE Instructor (
    instructor_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    photo VARCHAR(255),
    specialization TEXT,
    status ENUM('active','inactive','on_leave') DEFAULT 'active',
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TRAINING TYPE TABLE
-- ============================================
CREATE TABLE Training_Type (
    training_type_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_minutes INT,
    difficulty ENUM('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. INSTRUCTOR TRAINING TYPE (Many-to-Many)
-- ============================================
CREATE TABLE Instructor_Train_Type (
    instructor_id INT,
    training_type_id INT,
    PRIMARY KEY (instructor_id, training_type_id),
    FOREIGN KEY (instructor_id) REFERENCES Instructor(instructor_id) ON DELETE CASCADE,
    FOREIGN KEY (training_type_id) REFERENCES Training_Type(training_type_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TRAINING SECTION TABLE
-- ============================================
CREATE TABLE Training_Section (
    section_id INT PRIMARY KEY AUTO_INCREMENT,
    training_type_id INT,
    instructor_id INT,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
    start_time TIME,
    end_time TIME,
    capacity INT DEFAULT 20,
    status ENUM('active','cancelled','full') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (training_type_id) REFERENCES Training_Type(training_type_id),
    FOREIGN KEY (instructor_id) REFERENCES Instructor(instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. MEMBERS TABLE
-- ============================================
CREATE TABLE Members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    health_status TEXT,
    membership_type_id INT,
    index_number VARCHAR(50),
    profile_photo VARCHAR(255),
    registration_date DATE,
    status ENUM('pending','approved','suspended','expired') DEFAULT 'pending',
    assigned_instructor_id INT,
    membership_start_date DATE,
    membership_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_type_id) REFERENCES Membership_Type(membership_type_id),
    FOREIGN KEY (assigned_instructor_id) REFERENCES Instructor(instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. MEMBER SECTION (Many-to-Many)
-- ============================================
CREATE TABLE Member_Section (
    member_id INT,
    section_id INT,
    enrollment_date DATE,
    attendance_count INT DEFAULT 0,
    status ENUM('active','completed','dropped') DEFAULT 'active',
    PRIMARY KEY (member_id, section_id),
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES Training_Section(section_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. PAYMENT TABLE
-- ============================================
CREATE TABLE Payment (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','card','bank_transfer','mobile_money') NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(100) UNIQUE,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    description TEXT,
    receipt_path VARCHAR(255),
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ADMIN USERS TABLE
-- ============================================
CREATE TABLE Admin_Users (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin','admin','manager','staff') DEFAULT 'admin',
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account so the admin panel is usable right after import.
-- Login:  username = admin   |   password = admin123
-- IMPORTANT: change this password after first login.
INSERT INTO Admin_Users (username, password_hash, email, full_name, role, status)
VALUES ('admin', '$2y$12$3CNT7CilV7si6dOIuI5cK.2VthwuCpotfNumUkjQqaGJWKhJU4l6u', 'admin@ustedk.com', 'System Administrator', 'super_admin', 'active');

-- ============================================
-- 10. SYSTEM LOGS TABLE
-- ============================================
CREATE TABLE System_Logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NULL,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Admin_Users(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE Notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NULL,
    admin_id INT NULL,
    title VARCHAR(255),
    message TEXT,
    type ENUM('info','success','warning','error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES Admin_Users(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. SYSTEM SETTINGS TABLE
-- ============================================
CREATE TABLE System_Settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. LOGIN ATTEMPTS TABLE
-- ============================================
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) UNIQUE,
    attempts INT DEFAULT 0,
    last_attempt DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;