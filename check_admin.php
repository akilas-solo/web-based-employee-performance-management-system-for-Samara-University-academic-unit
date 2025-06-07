<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin User Check Script
 */

// Include configuration file
require_once 'includes/config.php';

// Check if admin user exists
$sql = "SELECT * FROM users WHERE role = 'admin'";
$result = $conn->query($sql);

echo "<h2>Admin User Check</h2>";

if ($result && $result->num_rows > 0) {
    echo "<p>Admin user(s) found:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No admin users found in the database.</p>";
    
    // Create admin user if none exists
    echo "<p>Creating default admin user...</p>";
    
    $username = 'admin';
    $email = 'admin@samarauniversity.edu.et';
    $password = password_hash_custom('password');
    $full_name = 'System Administrator';
    $role = 'admin';
    
    $sql = "INSERT INTO users (username, password, email, full_name, role) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $password, $email, $full_name, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        echo "<p>Admin user created successfully with ID: " . $user_id . "</p>";
        
        // Create admin profile
        $sql = "INSERT INTO admin_profiles (user_id, access_level, last_password_change) 
                VALUES (?, 'super', CURDATE())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo "<p>Admin profile created successfully.</p>";
        } else {
            echo "<p>Error creating admin profile: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>Error creating admin user: " . $stmt->error . "</p>";
    }
}

// Close connection
$conn->close();

echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
