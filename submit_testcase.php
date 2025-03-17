<?php
session_start();
header("Content-Type: application/json"); // Set response type to JSON

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in."]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "testing_db");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Read and decode JSON input
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON format"]);
    exit();
}

// Extract and validate input data
$id = $data['testcase_id'] ?? '';
$product_names = $data['product_name'] ?? [];
$version = $data['version'] ?? '';
$module_name = $data['module_name'] ?? '';
$description = $data['description'] ?? '';
$preconditions = $data['preconditions'] ?? '';
$test_steps = $data['test_steps'] ?? '';
$expected_results = $data['expected_results'] ?? '';

if (empty($product_names) || empty($version) || empty($module_name) || empty($description)) {
    echo json_encode(["status" => "error", "message" => "All required fields must be filled."]);
    exit();
}

if (!empty($id)) {
    // **Update Existing Test Case - Delete previous entry**
    $stmt = $conn->prepare("DELETE FROM testcase WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Failed to update test case."]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
}

// Insert a new row for each selected product
foreach ($product_names as $product_name) {
    $stmt = $conn->prepare("INSERT INTO testcase (Product_name, Version, Module_name, description, preconditions, test_steps, expected_results) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $product_name, $version, $module_name, $description, $preconditions, $test_steps, $expected_results);

    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Error inserting test case: " . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
}

$conn->close();
echo json_encode(["status" => "success", "message" => "Test case saved successfully"]);
?>
