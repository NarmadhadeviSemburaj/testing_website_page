<?php
session_start();
require 'vendor/autoload.php'; // Load PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Debugging: Log the request method and files
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Files: " . print_r($_FILES, true));

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

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

        if (count($data) <= 1) {
            echo json_encode(["status" => "error", "message" => "No test cases found in the file"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO testcase (Product_name, Version, Module_name, description, preconditions, test_steps, expected_results) VALUES (?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit;
        }

        $insertedRows = 0;
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            $product_name = $row[0] ?? '';
            $version = $row[1] ?? '';
            $module_name = $row[2] ?? '';
            $description = $row[3] ?? '';
            $preconditions = $row[4] ?? '';
            $test_steps = $row[5] ?? '';
            $expected_results = $row[6] ?? '';

            if (!empty($product_name) && !empty($version) && !empty($module_name)) {
                $stmt->bind_param("sssssss", $product_name, $version, $module_name, $description, $preconditions, $test_steps, $expected_results);
                if ($stmt->execute()) {
                    $insertedRows++;
                }
            }
        }
        echo json_encode(["status" => "success", "message" => "$insertedRows test cases uploaded successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error processing file: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "File upload error"]);
}

$conn->close();
?>