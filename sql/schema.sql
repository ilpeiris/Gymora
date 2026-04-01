-- Create the database and use it 
CREATE DATABASE IF NOT EXISTS gymora;
USE gymora;

-- 1. PACKAGES TABLE 
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price_gbp DECIMAL(8,2) NOT NULL,
    duration_months INT NOT NULL,
    consultation_count INT NOT NULL,
    features JSON,
    is_active TINYINT(1) DEFAULT 1
);

-- 2. USERS TABLE 
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','doctor','trainer','admin') NOT NULL,
    package_id INT,
    package_expiry DATE,
    consultations_remaining INT DEFAULT 0,
    consent_given TINYINT(1) DEFAULT 0,
    consent_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);

-- 3. EXERCISES TABLE 
CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    impact_level ENUM('low','medium','high'),
    muscle_groups VARCHAR(200),
    description TEXT,
    equipment_needed VARCHAR(100)
);

-- 4. CLASSES TABLE 
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    trainer_id INT,
    datetime DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    capacity INT NOT NULL,
    enrolled_count INT DEFAULT 0,
    impact_level ENUM('low','medium','high'),
    contraindication_tags JSON,
    location VARCHAR(100),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. MEDICAL ASSESSMENTS TABLE 
CREATE TABLE medical_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    bmi DECIMAL(5,2),
    weight_kg DECIMAL(5,2),
    height_cm DECIMAL(5,2),
    blood_pressure_sys INT,
    blood_pressure_dia INT,
    heart_rate_resting INT,
    notes_encrypted TEXT,
    diet_notes TEXT,
    supplement_notes TEXT,
    status ENUM('draft','submitted','archived') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

-- 6. MEDICAL CONDITIONS TABLE 
CREATE TABLE medical_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    condition_name VARCHAR(100) NOT NULL,
    severity INT NOT NULL CHECK (severity BETWEEN 1 AND 5),
    is_active TINYINT(1) DEFAULT 1,
    notes VARCHAR(255),
    FOREIGN KEY (assessment_id) REFERENCES medical_assessments(id) ON DELETE CASCADE
);

-- 7. DSS RULES TABLE 
CREATE TABLE dss_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    condition_name VARCHAR(100) NOT NULL,
    exercise_id INT NOT NULL,
    rule_type ENUM('BLOCK','WARN') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    alternative_exercise_id INT NULL,
    severity_threshold INT DEFAULT 1,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE,
    FOREIGN KEY (alternative_exercise_id) REFERENCES exercises(id) ON DELETE SET NULL
);

-- 8. WORKOUT PLANS TABLE
CREATE TABLE workout_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trainer_id INT NOT NULL,
    week_number INT NOT NULL,
    status ENUM('draft','active','completed') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES users(id)
);

-- 9. WORKOUT EXERCISES TABLE 
CREATE TABLE workout_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    exercise_id INT NOT NULL,
    sets INT NOT NULL,
    reps VARCHAR(20) NOT NULL,
    rest_seconds INT,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    dss_approved TINYINT(1) DEFAULT 0,
    notes VARCHAR(255),
    FOREIGN KEY (plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
);

-- 10. BOOKINGS TABLE 
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    booked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed','cancelled','blocked','attended') NOT NULL,
    dss_block_reason VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- 11. APPOINTMENTS TABLE 
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    staff_id INT NOT NULL,
    type ENUM('medical_consultation','training_session') NOT NULL,
    datetime DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    status ENUM('scheduled','completed','cancelled','no_show') NOT NULL,
    notes TEXT NULL,
    consultation_slot_used TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- 12. AUDIT LOGS TABLE 
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    record_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 13. MESSAGES TABLE 
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 14. PROGRESS LOGS TABLE 
CREATE TABLE progress_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    logged_by INT NOT NULL,
    log_date DATE NOT NULL,
    weight_kg DECIMAL(5,2) NULL,
    bmi DECIMAL(4,2) NULL,
    body_fat_pct DECIMAL(4,2) NULL,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES users(id)
);



-- test packages
INSERT INTO packages (name, price_gbp, duration_months, consultation_count, features, is_active) VALUES 
('Standard Plan', 29.99, 1, 1, '["Access to gym", "1 Doctor Consultation/mo", "Basic Classes"]', 1),
('Premium Medical Plan', 59.99, 1, 3, '["Priority gym access", "3 Doctor Consultations/mo", "All DSS-Approved Classes"]', 1);

-- blood pressure
ALTER TABLE medical_assessments ADD COLUMN blood_pressure VARCHAR(20) NULL AFTER bmi;