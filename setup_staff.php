<?php
/**
 * Samara University Academic Performance Evaluation System
 * Setup Staff Role
 */

// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in and has admin role
if (!is_logged_in() || !is_admin()) {
    // Display a message for non-admin users
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h2>Admin Access Required</h2>";
    echo "<p>You need to be logged in as an administrator to access this page.</p>";
    echo "<a href='" . $base_url . "login.php'>Go to Login Page</a>";
    echo "</div>";
    exit;
}

// Include the staff profiles creation script
include_once 'database/create_staff_profiles.php';
?>
