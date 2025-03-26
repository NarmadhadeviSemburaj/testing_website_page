<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Get the ID from the request
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

// Fetch test case - use quotes around the ID since it's VARCHAR
$sql = "SELECT * FROM testcase WHERE id = '$id'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'data' => $row
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test case not found'
    ]);
}

$conn->close();
?>