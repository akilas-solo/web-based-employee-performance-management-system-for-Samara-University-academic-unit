-- Samara University Academic Performance Evaluation System
-- Database Schema

-- Drop database if exists
DROP DATABASE IF EXISTS samara_evaluation;

-- Create database
CREATE DATABASE samara_evaluation;

-- Use database
USE samara_evaluation;

-- Admin table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Colleges table
CREATE TABLE colleges (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    vision TEXT,
    mission TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments table
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    college_id INT NOT NULL,
    description TEXT,
    vision TEXT,
    mission TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('college', 'dean', 'head_of_department', 'staff', 'hrm') NOT NULL,
    department_id INT DEFAULT NULL,
    college_id INT DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status TINYINT DEFAULT 1, -- 0: inactive, 1: active
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE SET NULL
);

-- College profiles
CREATE TABLE college_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    staff_count INT DEFAULT 0,
    established_year INT(4) DEFAULT NULL,
    achievements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Head of Department profiles
CREATE TABLE head_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_rank VARCHAR(50) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    appointment_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Dean profiles
CREATE TABLE dean_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_rank VARCHAR(50) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    appointment_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- HRM profiles
CREATE TABLE hrm_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Staff profiles
CREATE TABLE staff_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_rank VARCHAR(50) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    appointment_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Evaluation periods
CREATE TABLE evaluation_periods (
    period_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('I', 'II', 'Summer') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming', 'active', 'completed', 'archived') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Evaluation categories
CREATE TABLE evaluation_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Evaluation criteria
CREATE TABLE evaluation_criteria (
    criteria_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    min_rating INT NOT NULL DEFAULT 1,
    max_rating INT NOT NULL DEFAULT 5,
    evaluator_role ENUM('head_of_department', 'dean', 'college', 'hrm') NOT NULL,
    target_role ENUM('head_of_department', 'dean', 'college', 'hrm') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES evaluation_categories(category_id) ON DELETE CASCADE
);

-- Evaluations
CREATE TABLE evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    period_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    evaluatee_id INT NOT NULL,
    status ENUM('draft', 'submitted', 'reviewed', 'approved', 'rejected') DEFAULT 'draft',
    submission_date DATETIME DEFAULT NULL,
    total_score DECIMAL(5,2) DEFAULT 0,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (evaluatee_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Evaluation responses
CREATE TABLE evaluation_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    criteria_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    evidence VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(criteria_id) ON DELETE CASCADE
);

-- Performance reports
CREATE TABLE performance_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_id INT NOT NULL,
    department_id INT DEFAULT NULL,
    college_id INT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    average_score DECIMAL(5,2) DEFAULT 0,
    strengths TEXT,
    weaknesses TEXT,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE SET NULL
);

-- Notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- System settings
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin (username, password, email, full_name)
VALUES ('admin', '$2y$12$6Y7.S9RmBGAZOlpnwlO6UOyGxKIZa6L8eHRmY9vYzBALJtYTvVJVm', 'admin@samara.edu.et', 'System Administrator');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('site_name', 'Samara University Academic Performance Evaluation System'),
('site_logo', 'logo.png'),
('current_academic_year', '2023-2024'),
('current_semester', 'I'),
('evaluation_open', 'true'),
('primary_color', '#22AE9A'),
('secondary_color', '#1c8e7d'),
('head_color', '#3498DB'),
('dean_color', '#9B59B6'),
('college_color', '#2ECC71'),
('hrm_color', '#E67E22');
