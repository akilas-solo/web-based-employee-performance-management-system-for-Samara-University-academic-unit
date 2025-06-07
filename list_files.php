<?php
// List files in a directory
$directory = 'dean';
echo "<h2>Files in $directory directory:</h2>";
echo "<ul>";
if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
} else {
    echo "<li>Directory not found</li>";
}
echo "</ul>";

// Also check for head directory
$directory = 'head';
echo "<h2>Files in $directory directory:</h2>";
echo "<ul>";
if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
} else {
    echo "<li>Directory not found</li>";
}
echo "</ul>";
?>
