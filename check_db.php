<?php
// Include configuration file
require_once 'includes/config.php';

// Check if hrm_profiles table exists
$result = $conn->query("SHOW TABLES LIKE 'hrm_profiles'");
echo "hrm_profiles table: " . ($result->num_rows > 0 ? "exists" : "does not exist") . "<br>";

// Check if notification_settings table exists
$result = $conn->query("SHOW TABLES LIKE 'notification_settings'");
echo "notification_settings table: " . ($result->num_rows > 0 ? "exists" : "does not exist") . "<br>";

// Check if privacy_settings table exists
$result = $conn->query("SHOW TABLES LIKE 'privacy_settings'");
echo "privacy_settings table: " . ($result->num_rows > 0 ? "exists" : "does not exist") . "<br>";

// Check if get_user_by_id function works
echo "Testing get_user_by_id function:<br>";
$user = get_user_by_id(2); // HRM user ID
echo "<pre>";
print_r($user);
echo "</pre>";
?>
