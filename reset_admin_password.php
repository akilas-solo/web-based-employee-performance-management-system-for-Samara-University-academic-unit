<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin Password Reset Script
 */

// Include configuration file
require_once 'includes/config.php';

// Set default password
$default_password = 'password';
$hashed_password = password_hash_custom($default_password);

// Update admin password
$sql = "UPDATE users SET password = ? WHERE role = 'admin' AND username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);

echo "<h2>Admin Password Reset</h2>";

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p>Admin password has been reset successfully.</p>";
        echo "<p>You can now log in with:</p>";
        echo "<ul>";
        echo "<li>Email: admin@samarauniversity.edu.et</li>";
        echo "<li>Password: password</li>";
        echo "<li>Role: Admin</li>";
        echo "</ul>";
    } else {
        echo "<p>No admin user found to reset password. Please run check_admin.php first to create an admin user.</p>";
    }
} else {
    echo "<p>Error resetting admin password: " . $stmt->error . "</p>";
}

// Close connection
$conn->close();

echo "<p><a href='login.php'>Go to Login Page</a> | <a href='check_admin.php'>Check/Create Admin User</a></p>";
?>
