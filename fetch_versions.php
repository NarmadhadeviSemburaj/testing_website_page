<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Function to extract version from the file name
function extractVersionFromFilename($filename) {
    // Match version patterns like "v1.0", "1.0", "1.0.1", etc.
    if (preg_match("/([Vv]\d+(?:\.\d+)*)/", $filename, $matches)) {
        return htmlspecialchars($matches[1]); // Return the version number only
    }
    return "Unknown Version"; // Default if no version is found
}

// Validate folder names to prevent directory traversal attacks
if (isset($_GET['folders'])) {
    $folders = explode(',', $_GET['folders']);
    $versions = [];

    foreach ($folders as $folder) {
        $folder = basename($folder); // Prevent directory traversal
        $directory = "uploads/" . $folder;

        // Check if the directory exists
        if (is_dir($directory)) {
            // Scan for APK files in the directory
            $apkFiles = glob($directory . "/*.apk");

            if (!empty($apkFiles)) {
                // Extract versions from filenames
                foreach ($apkFiles as $apk) {
                    $apkFilename = basename($apk);
                    $version = extractVersionFromFilename($apkFilename);
                    if (!in_array($version, $versions)) { // Avoid duplicates
                        $versions[] = $version;
                    }
                }
            }
        }
    }

    // Return JSON response
    if (!empty($versions)) {
        echo json_encode(["status" => "success", "message" => "Versions retrieved successfully", "data" => $versions]);
    } else {
        echo json_encode(["status" => "error", "message" => "No APK files found", "data" => []]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Folders not specified", "data" => []]);
}
?>
