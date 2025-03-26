<?php
session_start();
include 'log_api.php';

// Enhanced security check with logging
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Log unauthorized access attempt
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'unauthorized_access',
        "Attempted to access APK API without admin privileges",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("HTTP/1.1 403 Forbidden");
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Admins only.'
    ]);
    exit();
}

header('Content-Type: application/json');

// Function to safely delete a folder with logging
function deleteFolder($folder) {
    if (!is_dir($folder)) {
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'folder_operation',
            "Attempted to delete non-existent folder: $folder",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            400
        );
        return false;
    }
    
    foreach (scandir($folder) as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = "$folder/$file";
            is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
        }
    }
    
    $success = rmdir($folder);
    if ($success) {
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'folder_deleted',
            "Successfully deleted folder: $folder",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            200
        );
    }
    return $success;
}

// Handle folder creation with overwrite option
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
    $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['folder_name']);
    $folder_path = "uploads/$folder_name";
    $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] == "yes";

    if (is_dir($folder_path)) {
        if ($overwrite) {
            if (deleteFolder($folder_path)) {
                mkdir($folder_path, 0777, true);
                
                // Log successful overwrite
                logUserAction(
                    $_SESSION['emp_id'],
                    $_SESSION['user'],
                    'folder_overwritten',
                    "Successfully overwrote folder: $folder_name",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REQUEST_METHOD'],
                    null,
                    200
                );
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Folder overwritten successfully.'
                ]);
            } else {
                // Log overwrite failure
                logUserAction(
                    $_SESSION['emp_id'],
                    $_SESSION['user'],
                    'folder_overwrite_failed',
                    "Failed to overwrite folder: $folder_name",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REQUEST_METHOD'],
                    null,
                    500
                );
                
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to overwrite folder.'
                ]);
            }
        } else {
            // Log folder exists warning
            logUserAction(
                $_SESSION['emp_id'],
                $_SESSION['user'],
                'folder_exists',
                "Folder already exists: $folder_name",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                null,
                200
            );
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Folder already exists. Overwrite?'
            ]);
        }
    } else {
        if (mkdir($folder_path, 0777, true)) {
            // Log successful creation
            logUserAction(
                $_SESSION['emp_id'],
                $_SESSION['user'],
                'folder_created',
                "Successfully created folder: $folder_name",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                null,
                200
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Folder created successfully.'
            ]);
        } else {
            // Log creation failure
            logUserAction(
                $_SESSION['emp_id'],
                $_SESSION['user'],
                'folder_creation_failed',
                "Failed to create folder: $folder_name",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                null,
                500
            );
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create folder.'
            ]);
        }
    }
    exit();
}

// Handle file upload with enhanced logging
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'upload_apk') {
    $folder_name = $_POST['folder_select'];
    $folder_path = "uploads/$folder_name";

    if (!is_dir($folder_path)) {
        // Log invalid folder attempt
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'invalid_folder',
            "Attempted to upload to non-existent folder: $folder_name",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            400
        );
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Folder does not exist.'
        ]);
        exit();
    }

    $file_name = $_FILES["apk_file"]["name"];
    $file_tmp = $_FILES["apk_file"]["tmp_name"];
    $file_path = "$folder_path/$file_name";

    // Validate file extension
    $allowed_extensions = ['apk'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        // Log invalid file type attempt
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'invalid_file_type',
            "Attempted to upload invalid file type: $file_name",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            400
        );
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Only APK files are allowed.'
        ]);
        exit();
    }

    if (move_uploaded_file($file_tmp, $file_path)) {
        // Log successful upload
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'apk_uploaded',
            "Successfully uploaded APK: $file_name to folder: $folder_name",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully.'
        ]);
    } else {
        // Log upload failure
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'apk_upload_failed',
            "Failed to upload APK: $file_name to folder: $folder_name",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            500,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode([
            'status' => 'error',
            'message' => 'File upload failed.'
        ]);
    }
    exit();
}

// Invalid request
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'unknown',
    'invalid_request',
    "Invalid request to APK API",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    400
);

http_response_code(400);
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request'
]);