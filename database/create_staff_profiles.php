<?php
/**
 * Samara University Academic Performance Evaluation System
 * Create Staff Profiles Table
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if staff_profiles table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'staff_profiles'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Create staff_profiles table if it doesn't exist
if (!$table_exists) {
    $sql = "CREATE TABLE IF NOT EXISTS staff_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        academic_rank VARCHAR(50) DEFAULT NULL,
        specialization VARCHAR(100) DEFAULT NULL,
        years_of_experience INT DEFAULT NULL,
        appointment_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "staff_profiles table created successfully.<br>";
        
        // Insert sample staff profiles for existing staff users
        $sql = "INSERT INTO staff_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date)
                SELECT user_id, 
                       CASE 
                           WHEN position LIKE '%Professor%' THEN SUBSTRING_INDEX(position, ',', 1)
                           WHEN position LIKE '%Lecturer%' THEN 'Lecturer'
                           ELSE 'Lecturer'
                       END as academic_rank,
                       CASE
                           WHEN position LIKE '%,%' THEN TRIM(SUBSTRING_INDEX(position, ',', -1))
                           ELSE ''
                       END as specialization,
                       FLOOR(RAND() * 10) + 1 as years_of_experience,
                       DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 1825) DAY) as appointment_date
                FROM users
                WHERE role = 'staff' AND user_id NOT IN (SELECT user_id FROM staff_profiles)";
        
        if ($conn->query($sql)) {
            echo "Sample staff profiles created for existing staff users.<br>";
        } else {
            echo "Error creating sample staff profiles: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating staff_profiles table: " . $conn->error . "<br>";
    }
} else {
    echo "staff_profiles table already exists.<br>";
}

// Update users table to include staff role if not already included
$sql = "SHOW COLUMNS FROM users LIKE 'role'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (strpos($row['Type'], 'staff') === false) {
        // Add staff to the role enum
        $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'dean', 'college', 'head_of_department', 'staff', 'hrm') NOT NULL";
        if ($conn->query($sql)) {
            echo "Added 'staff' to role enum in users table.<br>";
        } else {
            echo "Error updating role enum: " . $conn->error . "<br>";
        }
    } else {
        echo "Role enum already includes 'staff'.<br>";
    }
}

// Create user_settings table if it doesn't exist (used in staff/settings.php)
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'user_settings'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if (!$table_exists) {
    $sql = "CREATE TABLE IF NOT EXISTS user_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_notifications TINYINT(1) DEFAULT 1,
        dashboard_widgets VARCHAR(50) DEFAULT 'default',
        theme VARCHAR(50) DEFAULT 'light',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "user_settings table created successfully.<br>";
    } else {
        echo "Error creating user_settings table: " . $conn->error . "<br>";
    }
} else {
    echo "user_settings table already exists.<br>";
}

// Create evaluation_comments table if it doesn't exist (used in staff/evaluation_details.php)
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'evaluation_comments'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if (!$table_exists) {
    $sql = "CREATE TABLE IF NOT EXISTS evaluation_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        evaluation_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "evaluation_comments table created successfully.<br>";
    } else {
        echo "Error creating evaluation_comments table: " . $conn->error . "<br>";
    }
} else {
    echo "evaluation_comments table already exists.<br>";
}

echo "<br>Database setup for staff role completed successfully.";
echo "<br><a href='" . $base_url . "login.php'>Go to Login Page</a>";
?>
