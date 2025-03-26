<?php
header('Content-Type: application/json');
session_start();

// Include logging functions
include 'log_api.php';

// Log API request initiation
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'fetch_testcase_request',
    "Fetch testcase API called",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    json_encode($_GET),
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Database connection
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'fetch_testcase_db_error',
        "Database connection failed",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['error' => $conn->connect_error],
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Get the ID from the request
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : null;

if (!$id) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'fetch_testcase_missing_id',
        "ID parameter missing",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(['status' => 'error', 'message' => 'ID is required']);
    exit;
}

// Fetch test case
$sql = "SELECT * FROM testcase WHERE id = '$id'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'fetch_testcase_success',
        "Testcase fetched successfully",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['testcase_id' => $id],
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode([
        'status' => 'success',
        'data' => $row
    ]);
} else {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'fetch_testcase_not_found',
        "Testcase not found",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['testcase_id' => $id],
        404,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Test case not found'
    ]);
}

// Log API request completion
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'fetch_testcase_complete',
    "Fetch testcase API completed",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

$conn->close();
?>