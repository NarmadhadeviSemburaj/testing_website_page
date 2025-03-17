<?php
session_start();

// Ensure only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Fetch APK versions dynamically if requested via AJAX
if (isset($_GET['fetch_versions'])) {
    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['fetch_versions']); // Sanitize input
    $folder_path = "uploads/$folder";
    
    $versions = [];

    if (is_dir($folder_path)) {
        $files = array_values(array_diff(scandir($folder_path), array('.', '..')));

        foreach ($files as $file) {
            if (preg_match('/V[\d\.]+/', $file, $matches)) { // Extract version (e.g., V1.0)
                $versions[] = ['filename' => $file, 'version' => $matches[0]];
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($versions);
    exit(); // Stop further execution for AJAX request
}

header("HTTP/1.1 400 Bad Request");
exit();