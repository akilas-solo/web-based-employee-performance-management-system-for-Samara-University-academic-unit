-- Samara University Academic Performance Evaluation System
-- Database Schema

-- Drop existing tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evaluation_responses;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS evaluation_criteria;
DROP TABLE IF EXISTS evaluation_categories;
DROP TABLE IF EXISTS evaluation_periods;
DROP TABLE IF EXISTS admin_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS colleges;
SET FOREIGN_KEY_CHECKS = 1;

-- Create colleges table
CREATE TABLE IF NOT EXISTS colleges (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    vision TEXT,
    mission TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    college_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'dean', 'college', 'head_of_department', 'staff', 'hrm') NOT NULL,
    department_id INT,
    college_id INT,
    position VARCHAR(100),
    profile_image VARCHAR(255),
    bio TEXT,
    phone VARCHAR(20),
    address TEXT,
    status TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create evaluation periods table
CREATE TABLE IF NOT EXISTS evaluation_periods (
    period_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create evaluation categories table
CREATE TABLE IF NOT EXISTS evaluation_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create evaluation criteria table
CREATE TABLE IF NOT EXISTS evaluation_criteria (
    criteria_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) DEFAULT 1.00,
    min_rating INT DEFAULT 1,
    max_rating INT DEFAULT 5,
    evaluator_roles VARCHAR(255) DEFAULT 'all',
    evaluatee_roles VARCHAR(255) DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES evaluation_categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create evaluations table
CREATE TABLE IF NOT EXISTS evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluator_id INT NOT NULL,
    evaluatee_id INT NOT NULL,
    period_id INT NOT NULL,
    total_score DECIMAL(5,2) DEFAULT 0.00,
    comments TEXT,
    status ENUM('draft', 'submitted', 'reviewed', 'approved', 'rejected') DEFAULT 'draft',
    submission_date DATETIME,
    review_date DATETIME,
    approval_date DATETIME,
    rejection_date DATETIME,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (evaluatee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES evaluation_periods(period_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create evaluation responses table
CREATE TABLE IF NOT EXISTS evaluation_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    criteria_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(criteria_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create HRM profiles table
CREATE TABLE IF NOT EXISTS hrm_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create dean profiles table
CREATE TABLE IF NOT EXISTS dean_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_rank VARCHAR(50) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    appointment_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create head of department profiles table
CREATE TABLE IF NOT EXISTS head_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_rank VARCHAR(50) DEFAULT NULL,
    specialization VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    appointment_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create college profiles table
CREATE TABLE IF NOT EXISTS college_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    staff_count INT DEFAULT 0,
    established_year INT(4) DEFAULT NULL,
    achievements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin profiles table
CREATE TABLE IF NOT EXISTS admin_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    access_level ENUM('super', 'standard') DEFAULT 'standard',
    last_password_change DATE DEFAULT NULL,
    security_question VARCHAR(255) DEFAULT NULL,
    security_answer VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data

-- Insert default colleges
INSERT INTO colleges (name, code, description)
SELECT 'College of Computing and Informatics', 'COCS', 'The College of Computing and Informatics at Samara University' FROM dual WHERE NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'COCS');

INSERT INTO colleges (name, code, description)
SELECT 'College of Engineering', 'COE', 'The College of Engineering at Samara University' FROM dual WHERE NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'COE');

INSERT INTO colleges (name, code, description)
SELECT 'College of Health Sciences', 'COHS', 'The College of Health Sciences at Samara University' FROM dual WHERE NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'COHS');

INSERT INTO colleges (name, code, description)
SELECT 'College of Business and Economics', 'COBE', 'The College of Business and Economics at Samara University' FROM dual WHERE NOT EXISTS (SELECT 1 FROM colleges WHERE code = 'COBE');

-- Insert default departments
INSERT INTO departments (name, code, college_id, description)
SELECT 'Computer Science', 'CS', 1, 'Department of Computer Science' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'CS');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Information Technology', 'IT', 1, 'Department of Information Technology' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'IT');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Software Engineering', 'SE', 1, 'Department of Software Engineering' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'SE');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Electrical Engineering', 'EE', 2, 'Department of Electrical Engineering' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'EE');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Civil Engineering', 'CE', 2, 'Department of Civil Engineering' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'CE');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Mechanical Engineering', 'ME', 2, 'Department of Mechanical Engineering' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'ME');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Medicine', 'MED', 3, 'Department of Medicine' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'MED');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Nursing', 'NUR', 3, 'Department of Nursing' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'NUR');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Accounting', 'ACC', 4, 'Department of Accounting' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'ACC');

INSERT INTO departments (name, code, college_id, description)
SELECT 'Management', 'MGT', 4, 'Department of Management' FROM dual WHERE NOT EXISTS (SELECT 1 FROM departments WHERE code = 'MGT');

-- Insert default admin user
INSERT INTO users (username, password, email, full_name, role)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@samarauniversity.edu.et', 'System Administrator', 'admin'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Insert admin profile
INSERT INTO admin_profiles (user_id, access_level, last_password_change)
SELECT (SELECT user_id FROM users WHERE username = 'admin'), 'super', CURDATE()
FROM dual WHERE NOT EXISTS (SELECT 1 FROM admin_profiles WHERE user_id = (SELECT user_id FROM users WHERE username = 'admin'));

-- Insert HRM user
INSERT INTO users (username, password, email, full_name, role, position, phone)
SELECT 'hrm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hrm@samarauniversity.edu.et', 'HR Manager', 'hrm', 'Human Resources Manager', '+251911234567'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'hrm');

-- Insert college representatives
INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cocs_rep', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cocs@samarauniversity.edu.et', 'Computing College Rep', 'college', 1, 'College Representative', '+251922345678'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cocs_rep');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'coe_rep', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coe@samarauniversity.edu.et', 'Engineering College Rep', 'college', 2, 'College Representative', '+251933456789'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'coe_rep');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cohs_rep', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cohs@samarauniversity.edu.et', 'Health Sciences College Rep', 'college', 3, 'College Representative', '+251944567890'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cohs_rep');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cobe_rep', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cobe@samarauniversity.edu.et', 'Business College Rep', 'college', 4, 'College Representative', '+251955678901'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cobe_rep');

-- Insert deans
INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cocs_dean', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cocs.dean@samarauniversity.edu.et', 'Dr. Abebe Bekele', 'dean', 1, 'Dean, College of Computing', '+251966789012'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cocs_dean');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'coe_dean', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coe.dean@samarauniversity.edu.et', 'Dr. Kebede Tadesse', 'dean', 2, 'Dean, College of Engineering', '+251977890123'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'coe_dean');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cohs_dean', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cohs.dean@samarauniversity.edu.et', 'Dr. Almaz Haile', 'dean', 3, 'Dean, College of Health Sciences', '+251988901234'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cohs_dean');

INSERT INTO users (username, password, email, full_name, role, college_id, position, phone)
SELECT 'cobe_dean', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cobe.dean@samarauniversity.edu.et', 'Dr. Tigist Mengistu', 'dean', 4, 'Dean, College of Business', '+251999012345'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cobe_dean');

-- Insert department heads
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'cs_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs.head@samarauniversity.edu.et', 'Dr. Solomon Mulugeta', 'head_of_department', 1, 1, 'Head, Computer Science', '+251900123456'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cs_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'it_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'it.head@samarauniversity.edu.et', 'Dr. Hiwot Yohannes', 'head_of_department', 2, 1, 'Head, Information Technology', '+251901234567'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'it_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'se_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'se.head@samarauniversity.edu.et', 'Dr. Dawit Alemu', 'head_of_department', 3, 1, 'Head, Software Engineering', '+251902345678'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'se_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ee_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ee.head@samarauniversity.edu.et', 'Dr. Yonas Tadesse', 'head_of_department', 4, 2, 'Head, Electrical Engineering', '+251903456789'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ee_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ce_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ce.head@samarauniversity.edu.et', 'Dr. Meron Negash', 'head_of_department', 5, 2, 'Head, Civil Engineering', '+251904567890'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ce_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'me_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'me.head@samarauniversity.edu.et', 'Dr. Bereket Hailu', 'head_of_department', 6, 2, 'Head, Mechanical Engineering', '+251905678901'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'me_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'med_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'med.head@samarauniversity.edu.et', 'Dr. Rahel Tesfaye', 'head_of_department', 7, 3, 'Head, Medicine', '+251906789012'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'med_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'nur_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nur.head@samarauniversity.edu.et', 'Dr. Samuel Girma', 'head_of_department', 8, 3, 'Head, Nursing', '+251907890123'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'nur_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'acc_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acc.head@samarauniversity.edu.et', 'Dr. Kidist Abebe', 'head_of_department', 9, 4, 'Head, Accounting', '+251908901234'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'acc_head');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'mgt_head', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mgt.head@samarauniversity.edu.et', 'Dr. Henok Getachew', 'head_of_department', 10, 4, 'Head, Management', '+251909012345'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'mgt_head');

-- Insert staff members
-- Computer Science Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'cs_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs.staff1@samarauniversity.edu.et', 'Alem Tsegaye', 'staff', 1, 1, 'Lecturer, Computer Science', '+251910123456'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cs_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'cs_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs.staff2@samarauniversity.edu.et', 'Belete Shiferaw', 'staff', 1, 1, 'Assistant Professor, Computer Science', '+251911234567'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cs_staff2');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'cs_staff3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cs.staff3@samarauniversity.edu.et', 'Chaltu Debela', 'staff', 1, 1, 'Associate Professor, Computer Science', '+251912345678'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'cs_staff3');

-- Information Technology Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'it_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'it.staff1@samarauniversity.edu.et', 'Daniel Mekonnen', 'staff', 2, 1, 'Lecturer, Information Technology', '+251913456789'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'it_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'it_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'it.staff2@samarauniversity.edu.et', 'Eleni Tadesse', 'staff', 2, 1, 'Assistant Professor, Information Technology', '+251914567890'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'it_staff2');

-- Software Engineering Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'se_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'se.staff1@samarauniversity.edu.et', 'Fasil Alemayehu', 'staff', 3, 1, 'Lecturer, Software Engineering', '+251915678901'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'se_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'se_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'se.staff2@samarauniversity.edu.et', 'Genet Bekele', 'staff', 3, 1, 'Assistant Professor, Software Engineering', '+251916789012'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'se_staff2');

-- Electrical Engineering Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ee_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ee.staff1@samarauniversity.edu.et', 'Habtamu Worku', 'staff', 4, 2, 'Lecturer, Electrical Engineering', '+251917890123'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ee_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ee_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ee.staff2@samarauniversity.edu.et', 'Iman Jemal', 'staff', 4, 2, 'Assistant Professor, Electrical Engineering', '+251918901234'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ee_staff2');

-- Civil Engineering Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ce_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ce.staff1@samarauniversity.edu.et', 'Jemal Hassan', 'staff', 5, 2, 'Lecturer, Civil Engineering', '+251919012345'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ce_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'ce_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ce.staff2@samarauniversity.edu.et', 'Konjit Ayele', 'staff', 5, 2, 'Assistant Professor, Civil Engineering', '+251920123456'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'ce_staff2');

-- Mechanical Engineering Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'me_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'me.staff1@samarauniversity.edu.et', 'Lemma Teshome', 'staff', 6, 2, 'Lecturer, Mechanical Engineering', '+251921234567'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'me_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'me_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'me.staff2@samarauniversity.edu.et', 'Meskerem Alemu', 'staff', 6, 2, 'Assistant Professor, Mechanical Engineering', '+251922345678'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'me_staff2');

-- Medicine Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'med_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'med.staff1@samarauniversity.edu.et', 'Netsanet Bekele', 'staff', 7, 3, 'Lecturer, Medicine', '+251923456789'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'med_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'med_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'med.staff2@samarauniversity.edu.et', 'Omer Ibrahim', 'staff', 7, 3, 'Assistant Professor, Medicine', '+251924567890'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'med_staff2');

-- Nursing Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'nur_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nur.staff1@samarauniversity.edu.et', 'Paulos Girma', 'staff', 8, 3, 'Lecturer, Nursing', '+251925678901'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'nur_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'nur_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nur.staff2@samarauniversity.edu.et', 'Rahel Solomon', 'staff', 8, 3, 'Assistant Professor, Nursing', '+251926789012'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'nur_staff2');

-- Accounting Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'acc_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acc.staff1@samarauniversity.edu.et', 'Samuel Tekle', 'staff', 9, 4, 'Lecturer, Accounting', '+251927890123'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'acc_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'acc_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acc.staff2@samarauniversity.edu.et', 'Tigist Haile', 'staff', 9, 4, 'Assistant Professor, Accounting', '+251928901234'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'acc_staff2');

-- Management Department Staff
INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'mgt_staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mgt.staff1@samarauniversity.edu.et', 'Usman Ali', 'staff', 10, 4, 'Lecturer, Management', '+251929012345'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'mgt_staff1');

INSERT INTO users (username, password, email, full_name, role, department_id, college_id, position, phone)
SELECT 'mgt_staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mgt.staff2@samarauniversity.edu.et', 'Wubit Mengistu', 'staff', 10, 4, 'Assistant Professor, Management', '+251930123456'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'mgt_staff2');

-- Insert default evaluation categories
INSERT INTO evaluation_categories (name, description)
SELECT 'Teaching Performance', 'Evaluation of teaching methods, materials, and effectiveness'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_categories WHERE name = 'Teaching Performance');

INSERT INTO evaluation_categories (name, description)
SELECT 'Research Output', 'Evaluation of research publications, projects, and impact'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_categories WHERE name = 'Research Output');

INSERT INTO evaluation_categories (name, description)
SELECT 'Community Service', 'Evaluation of contributions to the university and wider community'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_categories WHERE name = 'Community Service');

INSERT INTO evaluation_categories (name, description)
SELECT 'Professional Development', 'Evaluation of continuous learning and skill improvement'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_categories WHERE name = 'Professional Development');

INSERT INTO evaluation_categories (name, description)
SELECT 'Administrative Duties', 'Evaluation of administrative responsibilities and leadership'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_categories WHERE name = 'Administrative Duties');

-- Insert default evaluation criteria
INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 1, 'Course Material Quality', 'Quality and relevance of course materials provided to students', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 1 AND name = 'Course Material Quality');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 1, 'Teaching Methodology', 'Effectiveness of teaching methods and approaches', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 1 AND name = 'Teaching Methodology');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 1, 'Student Engagement', 'Ability to engage and motivate students', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 1 AND name = 'Student Engagement');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 1, 'Assessment Methods', 'Fairness and effectiveness of assessment methods', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 1 AND name = 'Assessment Methods');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 2, 'Publication Quality', 'Quality of research publications in reputable journals', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 2 AND name = 'Publication Quality');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 2, 'Research Projects', 'Involvement in research projects and grants', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 2 AND name = 'Research Projects');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 2, 'Research Impact', 'Impact and relevance of research to the field', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 2 AND name = 'Research Impact');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 3, 'University Service', 'Contribution to university committees and initiatives', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 3 AND name = 'University Service');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 3, 'Community Outreach', 'Engagement with the wider community', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 3 AND name = 'Community Outreach');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 4, 'Professional Training', 'Participation in professional development activities', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 4 AND name = 'Professional Training');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 4, 'Skill Enhancement', 'Improvement of relevant skills and competencies', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 4 AND name = 'Skill Enhancement');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 5, 'Leadership', 'Leadership qualities and effectiveness', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 5 AND name = 'Leadership');

INSERT INTO evaluation_criteria (category_id, name, description, weight)
SELECT 5, 'Administrative Efficiency', 'Efficiency in handling administrative tasks', 1.00
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_criteria WHERE category_id = 5 AND name = 'Administrative Efficiency');

-- Insert default evaluation periods
INSERT INTO evaluation_periods (title, academic_year, semester, start_date, end_date, description, status)
SELECT 'Mid-Year Evaluation 2023', '2022-2023', '2', '2023-01-01', '2023-01-31', 'Mid-year performance evaluation for the academic year 2022-2023', 'active'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_periods WHERE title = 'Mid-Year Evaluation 2023' AND academic_year = '2022-2023');

INSERT INTO evaluation_periods (title, academic_year, semester, start_date, end_date, description, status)
SELECT 'End-Year Evaluation 2023', '2022-2023', '2', '2023-05-01', '2023-05-31', 'End-year performance evaluation for the academic year 2022-2023', 'active'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_periods WHERE title = 'End-Year Evaluation 2023' AND academic_year = '2022-2023');

INSERT INTO evaluation_periods (title, academic_year, semester, start_date, end_date, description, status)
SELECT 'Mid-Year Evaluation 2024', '2023-2024', '1', '2023-12-01', '2023-12-31', 'Mid-year performance evaluation for the academic year 2023-2024', 'upcoming'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_periods WHERE title = 'Mid-Year Evaluation 2024' AND academic_year = '2023-2024');

-- Insert sample evaluations
-- College representative evaluating department heads
INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 3, 11, 1, 4.25, 'Dr. Solomon has shown excellent leadership in the Computer Science department. His initiatives have improved both teaching quality and research output.', 'submitted', '2023-01-15', '2023-01-10', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 3 AND evaluatee_id = 11 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 3, 12, 1, 3.85, 'Dr. Hiwot has made significant improvements to the IT curriculum. Some administrative processes could be more efficient.', 'submitted', '2023-01-16', '2023-01-12', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 3 AND evaluatee_id = 12 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 3, 13, 1, 4.10, 'Dr. Dawit has successfully established new industry partnerships for the Software Engineering department.', 'submitted', '2023-01-17', '2023-01-14', '2023-01-17'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 3 AND evaluatee_id = 13 AND period_id = 1);

-- Dean evaluating department heads
INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 7, 11, 1, 4.50, 'Excellent leadership and academic contributions. Dr. Solomon has exceeded expectations in research output and department management.', 'submitted', '2023-01-20', '2023-01-18', '2023-01-20'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 7 AND evaluatee_id = 11 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 7, 12, 1, 4.00, 'Good performance overall. Dr. Hiwot has shown strong teaching abilities and adequate administrative skills.', 'submitted', '2023-01-21', '2023-01-19', '2023-01-21'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 7 AND evaluatee_id = 12 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 7, 13, 1, 4.30, 'Very good performance. Dr. Dawit has demonstrated innovation in curriculum development and strong industry engagement.', 'submitted', '2023-01-22', '2023-01-20', '2023-01-22'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 7 AND evaluatee_id = 13 AND period_id = 1);

-- Department heads evaluating staff
INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 11, 21, 1, 3.95, 'Alem has shown dedication to teaching and student mentorship. Research output could be improved.', 'submitted', '2023-01-25', '2023-01-23', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 11 AND evaluatee_id = 21 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 11, 22, 1, 4.40, 'Belete has excellent research contributions and teaching evaluations. A valuable department member.', 'submitted', '2023-01-26', '2023-01-24', '2023-01-26'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 11 AND evaluatee_id = 22 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 11, 23, 1, 4.20, 'Chaltu has made significant contributions to both teaching and research. Community service could be enhanced.', 'submitted', '2023-01-27', '2023-01-25', '2023-01-27'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 11 AND evaluatee_id = 23 AND period_id = 1);

-- HRM evaluating college representatives
INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 2, 3, 1, 4.15, 'The Computing College representative has effectively managed college-wide initiatives and maintained good communication with departments.', 'submitted', '2023-01-28', '2023-01-26', '2023-01-28'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 2 AND evaluatee_id = 3 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 2, 4, 1, 3.90, 'The Engineering College representative has shown good administrative skills but could improve on strategic planning.', 'submitted', '2023-01-29', '2023-01-27', '2023-01-29'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 2 AND evaluatee_id = 4 AND period_id = 1);

-- Draft evaluations (not submitted)
INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 11, 24, 1, 0.00, 'Draft evaluation for Daniel.', 'draft', NULL, '2023-01-30', '2023-01-30'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 11 AND evaluatee_id = 24 AND period_id = 1);

INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, total_score, comments, status, submission_date, created_at, updated_at)
SELECT 12, 25, 1, 0.00, 'Draft evaluation for Eleni.', 'draft', NULL, '2023-01-30', '2023-01-30'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluations WHERE evaluator_id = 12 AND evaluatee_id = 25 AND period_id = 1);

-- Insert sample evaluation responses
-- Responses for evaluation ID 1 (College rep evaluating CS head)
INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 1, 5, 'Excellent course materials developed for the department.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 1);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 2, 4, 'Good teaching methodologies implemented across the department.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 2);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 5, 4, 'Strong publication record with high-impact journals.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 5);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 8, 4, 'Active participation in university committees.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 8);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 12, 5, 'Excellent leadership qualities demonstrated.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 12);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 1, 13, 4, 'Efficient handling of administrative tasks.', '2023-01-15', '2023-01-15'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 1 AND criteria_id = 13);

-- Responses for evaluation ID 2 (College rep evaluating IT head)
INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 1, 4, 'Good course materials but could be more comprehensive.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 1);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 2, 4, 'Effective teaching methods implemented.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 2);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 5, 3, 'Adequate publication record but could be improved.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 5);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 8, 4, 'Good service to university committees.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 8);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 12, 4, 'Good leadership qualities shown.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 12);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 2, 13, 4, 'Administrative tasks handled well.', '2023-01-16', '2023-01-16'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 2 AND criteria_id = 13);

-- Responses for evaluation ID 7 (CS head evaluating staff)
INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 1, 4, 'Well-prepared course materials.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 1);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 2, 4, 'Effective teaching methods used.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 2);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 3, 4, 'Good student engagement in classes.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 3);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 4, 4, 'Fair and comprehensive assessment methods.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 4);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 5, 3, 'Publication quality could be improved.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 5);

INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
SELECT 7, 6, 4, 'Active participation in research projects.', '2023-01-25', '2023-01-25'
FROM dual WHERE NOT EXISTS (SELECT 1 FROM evaluation_responses WHERE evaluation_id = 7 AND criteria_id = 6);

-- Insert profile data
-- HRM profile
INSERT INTO hrm_profiles (user_id, department, position, years_of_experience) VALUES
(2, 'Human Resources', 'Human Resources Manager', 8);

-- College profiles
INSERT INTO college_profiles (user_id, staff_count, established_year, achievements) VALUES
(3, 45, 2010, 'Established new research center, Increased international collaborations by 30%'),
(4, 60, 2008, 'Secured 3 major industry partnerships, Launched 2 new specialized programs'),
(5, 35, 2012, 'Improved student satisfaction ratings by 25%, Established community health initiatives'),
(6, 40, 2009, 'Launched entrepreneurship incubator, Secured international accreditation');

-- Dean profiles
INSERT INTO dean_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date) VALUES
(7, 'Professor', 'Computer Networks and Security', 15, '2020-09-01'),
(8, 'Professor', 'Structural Engineering', 18, '2019-07-01'),
(9, 'Professor', 'Public Health', 12, '2021-01-01'),
(10, 'Associate Professor', 'Finance and Economics', 10, '2022-01-01');

-- Head of Department profiles
INSERT INTO head_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date) VALUES
(11, 'Associate Professor', 'Artificial Intelligence', 10, '2021-09-01'),
(12, 'Associate Professor', 'Data Science', 8, '2022-01-01'),
(13, 'Associate Professor', 'Software Engineering', 9, '2021-06-01'),
(14, 'Professor', 'Power Systems', 12, '2020-09-01'),
(15, 'Associate Professor', 'Structural Engineering', 7, '2022-03-01'),
(16, 'Associate Professor', 'Thermodynamics', 8, '2021-09-01'),
(17, 'Professor', 'Internal Medicine', 15, '2019-09-01'),
(18, 'Associate Professor', 'Critical Care Nursing', 9, '2021-01-01'),
(19, 'Associate Professor', 'Financial Accounting', 8, '2022-01-01'),
(20, 'Associate Professor', 'Organizational Behavior', 7, '2022-06-01');
