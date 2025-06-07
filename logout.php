<?php
/**
 * Samara University Academic Performance Evaluation System
 * Logout Page
 */

// Include configuration file
require_once 'includes/config.php';

// Destroy session
session_destroy();

// Redirect to login page
redirect($base_url . 'login.php');
?>
