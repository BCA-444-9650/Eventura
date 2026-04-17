-- Eventura: Smart Event Management System
-- MySQL Database

-- Create database
CREATE DATABASE IF NOT EXISTS eventura CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eventura;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255), -- NULL for OAuth users
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL, -- Role set during registration/admin creation
    auth_type ENUM('email', 'google') DEFAULT 'email',
    google_id VARCHAR(255) UNIQUE,
    profile_completed BOOLEAN DEFAULT FALSE,
    department VARCHAR(100), -- Added for teacher profiles
    reset_token VARCHAR(255) NULL, -- For password reset functionality
    reset_expires TIMESTAMP NULL, -- Password reset token expiration
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- ============================================
-- 2. STUDENT_PROFILES TABLE
-- ============================================
CREATE TABLE student_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    roll_no VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 3. EVENTS TABLE
-- ============================================
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(200) NOT NULL,
    food_available BOOLEAN DEFAULT FALSE,
    max_participants INT DEFAULT 0, -- 0 means unlimited
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 4. EVENT_REGISTRATIONS TABLE
-- ============================================
CREATE TABLE event_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    student_profile_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    UNIQUE KEY unique_registration (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE
);

-- ============================================
-- 5. QR_CODES TABLE (Core Feature)
-- ============================================
CREATE TABLE qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT NOT NULL,
    qr_data VARCHAR(500) UNIQUE NOT NULL, -- Encoded QR data
    qr_image_path VARCHAR(255), -- Path to generated QR image
    entry_used BOOLEAN DEFAULT FALSE,
    entry_used_at TIMESTAMP NULL,
    entry_used_by INT NULL, -- User who scanned (teacher/admin)
    food_used BOOLEAN DEFAULT FALSE,
    food_used_at TIMESTAMP NULL,
    food_used_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES event_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (entry_used_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (food_used_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- Password: admin123
-- ============================================
INSERT INTO users (email, password, full_name, role, auth_type, profile_completed, is_active) 
VALUES (
    'admin@eventura.com',
    '$2y$10$q/DUbEyNwopyECobK32qJ.WWWMbrVtbOqjUWLHw53LmsdNQxZhGvW', -- admin123
    'System Administrator',
    'admin',
    'email',
    TRUE,
    TRUE
);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_google_id ON users(google_id);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_department ON users(department);
CREATE INDEX idx_student_profiles_user_id ON student_profiles(user_id);
CREATE INDEX idx_student_profiles_department ON student_profiles(department);
CREATE INDEX idx_student_profiles_course ON student_profiles(course);
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_created_by ON events(created_by);
CREATE INDEX idx_registrations_event_id ON event_registrations(event_id);
CREATE INDEX idx_registrations_user_id ON event_registrations(user_id);
CREATE INDEX idx_registrations_status ON event_registrations(status);
CREATE INDEX idx_qr_codes_registration ON qr_codes(registration_id);
CREATE INDEX idx_qr_codes_data ON qr_codes(qr_data);
CREATE INDEX idx_qr_codes_entry_used ON qr_codes(entry_used);
CREATE INDEX idx_qr_codes_food_used ON qr_codes(food_used);

-- ============================================
-- FINAL VERIFICATION
-- ============================================
-- Show all tables to verify creation
SHOW TABLES;
