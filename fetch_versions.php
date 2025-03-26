<?php
header('Content-Type: application/json');
session_start();

// Include logging functions
include 'log_api.php';

// Log API request initiation
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'fetch_versions_request',
    "Fetch versions API called",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    json_encode($_GET),
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Function to extract version from the file name
function extractVersionFromFilename($filename) {
    if (preg_match("/([Vv]\d+(?:\.\d+)*)/", $filename, $matches)) {
        return htmlspecialchars($matches[1]);
    }
    return "Unknown Version";
}

// Validate folder names to prevent directory traversal attacks
if (isset($_GET['folders'])) {
    $folders = explode(',', $_GET['folders']);
    $versions = [];
    $scannedFolders = [];

    foreach ($folders as $folder) {
        $folder = basename($folder);
        $directory = "uploads/" . $folder;
        $scannedFolders[] = $directory;

        if (is_dir($directory)) {
            $apkFiles = glob($directory . "/*.apk");

            if (!empty($apkFiles)) {
                foreach ($apkFiles as $apk) {
                    $apkFilename = basename($apk);
                    $version = extractVersionFromFilename($apkFilename);
                    if (!in_array($version, $versions)) {
                        $versions[] = $version;
                    }
                }
            }
        }
    }

    if (!empty($versions)) {
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'] ?? 'api_user',
            'fetch_versions_success',
            "Versions retrieved successfully",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['versions_count' => count($versions)],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(["status" => "success", "message" => "Versions retrieved successfully", "data" => $versions]);
    } else {
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'] ?? 'api_user',
            'fetch_versions_empty',
            "No APK files found",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['scanned_folders' => $scannedFolders],
            404,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(["status" => "error", "message" => "No APK files found", "data" => []]);
    }
} else {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'fetch_versions_missing_param',
        "Folders parameter not specified",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(["status" => "error", "message" => "Folders not specified", "data" => []]);
}

// Log API request completion
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'fetch_versions_complete',
    "Fetch versions API completed",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);
?>