<?php
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Remove header row
        array_shift($data);

        if (empty($data)) {
            echo json_encode(["status" => "error", "message" => "No test cases found in the file"]);
            exit;
        }

        // Prepare statement with NULL for test_result
        $stmt = $conn->prepare("INSERT INTO testcase 
                              (Product_name, Version, Module_name, description, 
                               preconditions, test_steps, expected_results, testing_result) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");

        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit;
        }

        $insertedRows = 0;
        $errors = [];
        
        foreach ($data as $index => $row) {
            // Map Excel columns to database fields
            $product_name = $row[0] ?? null;
            $version = $row[1] ?? null;
            $module_name = $row[2] ?? null;
            $description = $row[3] ?? null;
            $preconditions = $row[4] ?? null;
            $test_steps = $row[5] ?? null;
            $expected_results = $row[6] ?? null;

            // Skip empty rows
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
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error processing file: " . $e->getMessage()]);
    }
} else {
    $errorMsg = "No file uploaded";
    if (isset($_FILES['excel_file']['error'])) {
        $errorMsg .= " (Error code: " . $_FILES['excel_file']['error'] . ")";
    }
    echo json_encode(["status" => "error", "message" => $errorMsg]);
}

$conn->close();
?>