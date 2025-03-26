<?php
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['module_name']) || !isset($data['description'])) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Escape all inputs
$id = isset($data['id']) ? $conn->real_escape_string($data['id']) : null;
$products = array_map([$conn, 'real_escape_string'], $data['product_name']);
$version = $conn->real_escape_string($data['version']);
$module = $conn->real_escape_string($data['module_name']);
$desc = $conn->real_escape_string($data['description']);
$preconditions = $conn->real_escape_string($data['preconditions'] ?? '');
$steps = $conn->real_escape_string($data['test_steps']);
$results = $conn->real_escape_string($data['expected_results']);

// For multiple products, you might want to handle them differently
$product = $products[0]; // Or implement multi-product support

if ($id) {
    // Update existing record - don't modify testing_result unless explicitly provided
    $sql = "UPDATE testcase SET 
            Product_name = '$product',
            Version = '$version',
            Module_name = '$module',
            description = '$desc',
            preconditions = '$preconditions',
            test_steps = '$steps',
            expected_results = '$results'";
            
    // Only update testing_result if it was provided in the request
    if (isset($data['testing_result'])) {
        $testing_result = $conn->real_escape_string($data['testing_result']);
        $sql .= ", testing_result = '$testing_result'";
    }
    
    $sql .= " WHERE id = '$id'";
} else {
    // Insert new record - explicitly set testing_result to NULL
    $sql = "INSERT INTO testcase 
            (Product_name, Version, Module_name, description, 
             preconditions, test_steps, expected_results, testing_result)
            VALUES 
            ('$product', '$version', '$module', '$desc', 
             '$preconditions', '$steps', '$results', NULL)";
}

if ($conn->query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Test case saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error saving test case: ' . $conn->error]);
}

$conn->close();
?>