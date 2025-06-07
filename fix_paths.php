<?php
/**
 * Path Fix Script
 * This script updates all hardcoded paths in PHP files to use relative paths
 */

// Define the directories to scan
$directories = [
    'head',
    'college',
    'dean',
    'admin',
    'hrm'
];

// Define the patterns to search and replace
$patterns = [
    // Pattern 1: Hardcoded XAMPP path
    [
        'search' => "require_once 'C:/xampp/htdocs/samara_new/includes/config.php';",
        'replace' => "require_once dirname(__DIR__) . '/includes/config.php';"
    ],
    // Pattern 2: Hardcoded WAMP path
    [
        'search' => "require_once 'C:/wamp/www/samara_new/includes/config.php';",
        'replace' => "require_once dirname(__DIR__) . '/includes/config.php';"
    ],
    // Pattern 3: Hardcoded XAMPP path with double quotes
    [
        'search' => 'require_once "C:/xampp/htdocs/samara_new/includes/config.php";',
        'replace' => 'require_once dirname(__DIR__) . "/includes/config.php";'
    ],
    // Pattern 4: Hardcoded WAMP path with double quotes
    [
        'search' => 'require_once "C:/wamp/www/samara_new/includes/config.php";',
        'replace' => 'require_once dirname(__DIR__) . "/includes/config.php";'
    ],
    // Pattern 5: Include once with XAMPP path
    [
        'search' => "include_once 'C:/xampp/htdocs/samara_new/includes/config.php';",
        'replace' => "include_once dirname(__DIR__) . '/includes/config.php';"
    ],
    // Pattern 6: Include once with WAMP path
    [
        'search' => "include_once 'C:/wamp/www/samara_new/includes/config.php';",
        'replace' => "include_once dirname(__DIR__) . '/includes/config.php';"
    ]
];

// Counter for modified files
$modified_files = 0;
$modified_file_list = [];

// Process each directory
foreach ($directories as $directory) {
    // Check if directory exists
    if (!is_dir($directory)) {
        echo "Directory not found: $directory<br>";
        continue;
    }
    
    // Get all PHP files in the directory
    $files = glob($directory . '/*.php');
    
    // Process each file
    foreach ($files as $file) {
        // Read file content
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Apply all patterns
        foreach ($patterns as $pattern) {
            $content = str_replace($pattern['search'], $pattern['replace'], $content);
        }
        
        // If content was modified, write it back
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            $modified_files++;
            $modified_file_list[] = $file;
            echo "Updated file: $file<br>";
        }
    }
}

echo "<br>Total files modified: $modified_files<br>";

if (count($modified_file_list) > 0) {
    echo "<br>Modified files:<br>";
    echo "<ul>";
    foreach ($modified_file_list as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

echo "<br><a href='index.php'>Return to Home</a>";
?>
