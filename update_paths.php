<?php
/**
 * Path Update Script
 * This script updates all hardcoded paths from C:/xampp/htdocs/samara_new to C:/xampp/htdocs/samara_new
 */

// Define the paths
$old_path = 'C:/xampp/htdocs/samara_new';
$new_path = 'C:/xampp/htdocs/samara_new';

// Function to recursively find all PHP files
function findPhpFiles($dir) {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $result = array_merge($result, findPhpFiles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $result[] = $path;
        }
    }
    
    return $result;
}

// Function to update paths in a file
function updatePaths($file, $old_path, $new_path) {
    $content = file_get_contents($file);
    $updated_content = str_replace($old_path, $new_path, $content);
    
    if ($content !== $updated_content) {
        file_put_contents($file, $updated_content);
        return true;
    }
    
    return false;
}

// Get all PHP files
$files = findPhpFiles('.');
$updated_files = [];

// Update paths in each file
foreach ($files as $file) {
    if (updatePaths($file, $old_path, $new_path)) {
        $updated_files[] = $file;
    }
}

// Output results
echo "Path Update Complete\n";
echo "Updated " . count($updated_files) . " files:\n";

foreach ($updated_files as $file) {
    echo "- " . $file . "\n";
}
