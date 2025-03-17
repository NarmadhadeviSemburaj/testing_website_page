<?php
$conn = new mysqli("localhost", "root", "", "testing_db");

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed", "data" => null]));
}

// Get ID from request
$id = $_GET['id'] ?? null;

// Validate ID
if (!$id || !is_numeric($id)) {
    die(json_encode(["status" => "error", "message" => "Invalid or missing ID", "data" => null]));
}

// Fetch data
$result = $conn->query("SELECT * FROM testcase WHERE id = $id");

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(["status" => "success", "message" => "Record found", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => "No record found", "data" => null]);
}

$conn->close();
?>
