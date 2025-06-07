<?php
/**
 * Script to update include paths in PHP files
 * This script will replace dynamic dirname(__DIR__) calls with hardcoded paths
 */

// Define the directories to scan
$directories = [
    'head',
    'college',
    'dean',
    'admin'
];

// Define the base path
$base_path = 'C:/xampp/htdocs/samara_new';

// Define the old and new include patterns
$old_pattern = "require_once dirname(__DIR__) . '/includes/config.php';";
$new_pattern = "require_once 'C:/xampp/htdocs/samara_new/includes/config.php';";

$old_pattern2 = "require_once dirname(__DIR__) . \"/includes/config.php\";";
$new_pattern2 = "require_once \"C:/xampp/htdocs/samara_new/includes/config.php\";";

$old_pattern3 = '$parent_dir = dirname(__DIR__);
require_once $parent_dir . \'/includes/config.php\';';
$new_pattern3 = "require_once 'C:/xampp/htdocs/samara_new/includes/config.php';";

// Counter for modified files
$modified_files = 0;

// Process each directory
foreach ($directories as $directory) {
    $dir_path = $base_path . '/' . $directory;
    
    // Check if directory exists
    if (!is_dir($dir_path)) {
        echo "Directory not found: $dir_path<br>";
        continue;
    }
    
    // Get all PHP files in the directory
    $files = glob($dir_path . '/*.php');
    
    // Process each file
    foreach ($files as $file) {
        // Read file content
        $content = file_get_contents($file);
        
        // Check if the file contains the old pattern
        if (strpos($content, $old_pattern) !== false || 
            strpos($content, $old_pattern2) !== false || 
            strpos($content, $old_pattern3) !== false) {
            
            // Replace the patterns
            $new_content = str_replace($old_pattern, $new_pattern, $content);
            $new_content = str_replace($old_pattern2, $new_pattern2, $new_content);
            $new_content = str_replace($old_pattern3, $new_pattern3, $new_content);
            
            // Write the modified content back to the file
            file_put_contents($file, $new_content);
            
            // Increment counter
            $modified_files++;
            
            echo "Updated file: $file<br>";
        }
    }
}

echo "<br>Total files modified: $modified_files";
?>
