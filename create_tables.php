<?php
// Include configuration file
require_once 'includes/config.php';

// Create hrm_profiles table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS hrm_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    years_of_experience INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "hrm_profiles table created or already exists.<br>";
} else {
    echo "Error creating hrm_profiles table: " . $conn->error . "<br>";
}

// Create notification_settings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications TINYINT(1) DEFAULT 1,
    evaluation_reminders TINYINT(1) DEFAULT 1,
    staff_updates TINYINT(1) DEFAULT 1,
    system_updates TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "notification_settings table created or already exists.<br>";
} else {
    echo "Error creating notification_settings table: " . $conn->error . "<br>";
}

// Create privacy_settings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS privacy_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    show_email TINYINT(1) DEFAULT 1,
    show_phone TINYINT(1) DEFAULT 1,
    show_profile TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "privacy_settings table created or already exists.<br>";
} else {
    echo "Error creating privacy_settings table: " . $conn->error . "<br>";
}

// Check if HRM user exists
$sql = "SELECT * FROM users WHERE role = 'hrm' LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    // Create HRM user if not exists
    $password = password_hash_custom('password123');
    $sql = "INSERT INTO users (username, password, email, full_name, role, position, phone) 
            VALUES ('hrm', '$password', 'hrm@samarauniversity.edu.et', 'HR Manager', 'hrm', 'Human Resources Manager', '+251911234567')";
    if ($conn->query($sql)) {
        $user_id = $conn->insert_id;
        echo "HRM user created with ID: $user_id<br>";
        
        // Create HRM profile
        $sql = "INSERT INTO hrm_profiles (user_id, department, position, years_of_experience) 
                VALUES ($user_id, 'Human Resources', 'Human Resources Manager', 5)";
        if ($conn->query($sql)) {
            echo "HRM profile created.<br>";
        } else {
            echo "Error creating HRM profile: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating HRM user: " . $conn->error . "<br>";
    }
} else {
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    echo "HRM user already exists with ID: $user_id<br>";
    
    // Check if HRM profile exists
    $sql = "SELECT * FROM hrm_profiles WHERE user_id = $user_id";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        // Create HRM profile if not exists
        $sql = "INSERT INTO hrm_profiles (user_id, department, position, years_of_experience) 
                VALUES ($user_id, 'Human Resources', 'Human Resources Manager', 5)";
        if ($conn->query($sql)) {
            echo "HRM profile created.<br>";
        } else {
            echo "Error creating HRM profile: " . $conn->error . "<br>";
        }
    } else {
        echo "HRM profile already exists.<br>";
    }
}

echo "Done!";
?>
