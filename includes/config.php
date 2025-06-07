<?php
/**
 * Samara University Academic Performance Evaluation System
 * Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use a global variable instead of a constant to avoid memory allocation issues
if (!isset($GLOBALS['BASE_PATH'])) {
    // Dynamically determine the base path
    $script_filename = $_SERVER['SCRIPT_FILENAME'];
    $document_root = $_SERVER['DOCUMENT_ROOT'];

    // Remove document root from script filename to get the relative path
    $relative_path = str_replace($document_root, '', $script_filename);

    // Remove the script name and get the directory
    $relative_dir = dirname($relative_path);

    // If we're in a subdirectory, go up one level
    if (in_array(basename($relative_dir), ['admin', 'dean', 'college', 'head', 'hrm', 'staff'])) {
        $relative_dir = dirname($relative_dir);
    }

    // Set the base path
    $GLOBALS['BASE_PATH'] = $document_root . $relative_dir;
}

// For backward compatibility with existing code
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $GLOBALS['BASE_PATH']);
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'samara_evaluation');

// URL configuration
$base_url = 'http://localhost/samara_new/';

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define user roles
define('ROLE_ADMIN', 'admin');
define('ROLE_COLLEGE', 'college');
define('ROLE_DEAN', 'dean');
define('ROLE_HEAD', 'head_of_department');
define('ROLE_HRM', 'hrm');
define('ROLE_STAFF', 'staff');

// Define status constants
define('STATUS_INACTIVE', 0);
define('STATUS_ACTIVE', 1);

// Define theme colors
$theme_colors = [
    'admin' => [
        'primary' => '#22AE9A',
        'dark' => '#1c8e7d',
        'light' => '#7fd9ca',
        'very_light' => '#e7f7f5',
        'rgb' => '34, 174, 154'
    ],
    'college' => [
        'primary' => '#2ECC71',
        'dark' => '#27AE60',
        'light' => '#82E0AA',
        'very_light' => '#E9F7EF',
        'rgb' => '46, 204, 113'
    ],
    'dean' => [
        'primary' => '#9B59B6',
        'dark' => '#8E44AD',
        'light' => '#D2B4DE',
        'very_light' => '#F4ECF7',
        'rgb' => '155, 89, 182'
    ],
    'head_of_department' => [
        'primary' => '#3498DB',
        'dark' => '#2980B9',
        'light' => '#85C1E9',
        'very_light' => '#EBF5FB',
        'rgb' => '52, 152, 219'
    ],
    'hrm' => [
        'primary' => '#E67E22',
        'dark' => '#D35400',
        'light' => '#F0B27A',
        'very_light' => '#FDF2E9',
        'rgb' => '230, 126, 34'
    ],
    'staff' => [
        'primary' => '#6A5ACD',
        'dark' => '#483D8B',
        'light' => '#9E91E7',
        'very_light' => '#F0EEF9',
        'rgb' => '106, 90, 205'
    ]
];

// Include utility functions
require_once BASE_PATH . '/includes/functions.php';
?>
