<?php
/**
 * Samara University Academic Performance Evaluation System
 * Logo Update Script
 * 
 * This script updates the system logo settings to use the new logo file.
 */

// Include configuration
require_once 'includes/config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Error: Only administrators can run this script.";
    exit;
}

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update the logo setting
$sql = "UPDATE system_settings SET setting_value = 'logo.png' WHERE setting_key = 'site_logo'";

if ($conn->query($sql) === TRUE) {
    echo "Logo setting updated successfully.<br>";
} else {
    echo "Error updating logo setting: " . $conn->error . "<br>";
}

// Check if the logo file exists
if (file_exists('assets/images/logo.png')) {
    echo "Logo file exists at assets/images/logo.png<br>";
} else {
    echo "Warning: Logo file not found at assets/images/logo.png<br>";
}

// Close connection
$conn->close();

echo "<p>Logo update completed. The new logo will be used across the website.</p>";
echo "<p><a href='index.php'>Return to homepage</a></p>";
?>
