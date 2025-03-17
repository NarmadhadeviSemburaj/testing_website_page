<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'data' => null
    ]);
    exit();
}

include 'db_config.php'; // Include database configuration

// Set appropriate content type for JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getEmployees') {
    $sql = "SELECT * FROM employees";
    $result = $conn->query($sql);
    
    if ($result === false) {
        error_log("Database error: " . $conn->error);
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode([
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
            'data' => null
        ]);
        exit();
    }
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Employees retrieved successfully',
        'data' => $employees
    ]);
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request',
        'data' => null
    ]);
}

$conn->close();
?>
