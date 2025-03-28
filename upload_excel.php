<?php
header('Content-Type: application/json');
session_start();

// Include logging functions
include 'log_api.php';
include 'db_config.php';
// Log API request initiation
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'upload_excel_request',
    "Upload Excel API called",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Log file upload attempt
if (isset($_FILES['excel_file'])) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'upload_excel_file_received',
        "Excel file received",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        [
            'name' => $_FILES['excel_file']['name'],
            'size' => $_FILES['excel_file']['size'],
            'type' => $_FILES['excel_file']['type']
        ],
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        array_shift($data);

        if (empty($data)) {
            logUserAction(
                $_SESSION['emp_id'] ?? null,
                $_SESSION['user'] ?? 'api_user',
                'upload_excel_empty',
                "No test cases found in file",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                null,
                400,
                null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
            
            echo json_encode(["status" => "error", "message" => "No test cases found in the file"]);
            exit;
        }

        
        if ($conn->connect_error) {
            logUserAction(
                $_SESSION['emp_id'] ?? null,
                $_SESSION['user'] ?? 'api_user',
                'upload_excel_db_error',
                "Database connection failed",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                ['error' => $conn->connect_error],
                500,
                null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
            
            echo json_encode(["status" => "error", "message" => "Database connection failed"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO testcase 
                              (Product_name, Version, Module_name, description, 
                               preconditions, test_steps, expected_results, testing_result) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");

        if (!$stmt) {
            logUserAction(
                $_SESSION['emp_id'] ?? null,
                $_SESSION['user'] ?? 'api_user',
                'upload_excel_prepare_error',
                "Prepare statement failed",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                ['error' => $conn->error],
                500,
                null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
            
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit;
        }

        $insertedRows = 0;
        $errors = [];
        
        foreach ($data as $index => $row) {
            $product_name = $row[0] ?? null;
            $version = $row[1] ?? null;
            $module_name = $row[2] ?? null;
            $description = $row[3] ?? null;
            $preconditions = $row[4] ?? null;
            $test_steps = $row[5] ?? null;
            $expected_results = $row[6] ?? null;

            if (empty($product_name) || empty($version) || empty($module_name)) {
                $errors[] = "Skipped row " . ($index + 2) . " - missing required fields";
                continue;
            }

            $stmt->bind_param("sssssss", 
                $product_name, 
                $version, 
                $module_name, 
                $description, 
                $preconditions, 
                $test_steps, 
                $expected_results
            );

            if ($stmt->execute()) {
                $insertedRows++;
            } else {
                $errors[] = "Failed to insert row " . ($index + 2) . " - " . $stmt->error;
            }
        }

        $response = [
            "status" => "success",
            "message" => "$insertedRows test cases uploaded successfully"
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'] ?? 'api_user',
            'upload_excel_success',
            "Excel import completed",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            [
                'inserted_rows' => $insertedRows,
                'error_count' => count($errors),
                'first_errors' => array_slice($errors, 0, 3)
            ],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'] ?? 'api_user',
            'upload_excel_exception',
            "Error processing file",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['error' => $e->getMessage()],
            500,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(["status" => "error", "message" => "Error processing file: " . $e->getMessage()]);
    }
} else {
    $errorMsg = "No file uploaded";
    if (isset($_FILES['excel_file']['error'])) {
        $errorMsg .= " (Error code: " . $_FILES['excel_file']['error'] . ")";
    }
    
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'upload_excel_failed',
        $errorMsg,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['error_code' => $_FILES['excel_file']['error'] ?? null],
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(["status" => "error", "message" => $errorMsg]);
}

// Log API request completion
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'upload_excel_complete',
    "Upload Excel API completed",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);
?>