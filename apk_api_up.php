<?php
session_start();

// Ensure only admins can access
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

header('Content-Type: application/json');

// Function to safely delete a folder
function deleteFolder($folder) {
    if (!is_dir($folder)) return false;
    foreach (scandir($folder) as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = "$folder/$file";
            is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
        }
    }
    return rmdir($folder);
}

// Handle folder creation with overwrite option
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
    $folder_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['folder_name']); // Sanitize folder name
    $folder_path = "uploads/$folder_name";

    if (is_dir($folder_path)) {
        if (isset($_POST['overwrite']) && $_POST['overwrite'] == "yes") {
            deleteFolder($folder_path);
            mkdir($folder_path, 0777, true);
            echo json_encode([
                'status' => 'success',
                'message' => 'Folder overwritten successfully.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Folder already exists. Overwrite?'
            ]);
        }
    } else {
        mkdir($folder_path, 0777, true);
        echo json_encode([
            'status' => 'success',
            'message' => 'Folder created successfully.'
        ]);
    }
    exit();
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'upload_apk') {
    $folder_name = $_POST['folder_select'];
    $folder_path = "uploads/$folder_name";

    if (!is_dir($folder_path)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Folder does not exist.'
        ]);
        exit();
    }

    $file_name = $_FILES["apk_file"]["name"]; // Get filename
    $file_tmp = $_FILES["apk_file"]["tmp_name"];
    $file_path = "$folder_path/$file_name";

    if (move_uploaded_file($file_tmp, $file_path)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'File uploaded successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'File upload failed.'
        ]);
    }
    exit();
}

// Invalid request
http_response_code(400);
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request'
]);
?>
