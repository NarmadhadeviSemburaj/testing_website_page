<?php
session_start();

// Include logging functions
include 'log_api.php';
include 'db_config.php';
// Log deletion attempt initiation
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'unknown',
    'delete_testcase_attempt',
    "Attempting to delete test case",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    ['testcase_id' => $_GET['id'] ?? null],
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);



if ($conn->connect_error) {
    // Log database connection error
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'delete_testcase_db_error',
        "Database connection failed",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['error' => $conn->connect_error],
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Database connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;

// Validate the ID as a non-empty string
if (!$id || !is_string($id)) {
    // Log invalid ID error
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'delete_testcase_invalid_id',
        "Invalid or missing ID",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['received_id' => $id],
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Invalid or missing ID");
}

// First fetch the test case details for logging
$stmtSelect = $conn->prepare("SELECT Product_name, Version, Module_name FROM testcase WHERE id = ?");
$stmtSelect->bind_param("s", $id);
$stmtSelect->execute();
$result = $stmtSelect->get_result();
$testCaseDetails = $result->fetch_assoc();
$stmtSelect->close();

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM testcase WHERE id = ?");
$stmt->bind_param("s", $id); // Use "s" for string type

if ($stmt->execute()) {
    // Log successful deletion
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'delete_testcase_success',
        "Test case deleted successfully",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        [
            'testcase_id' => $id,
            'product' => $testCaseDetails['Product_name'] ?? 'unknown',
            'version' => $testCaseDetails['Version'] ?? 'unknown',
            'module' => $testCaseDetails['Module_name'] ?? 'unknown'
        ],
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("Location: index1.php");
    exit();
} else {
    // Log deletion failure
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'delete_testcase_failed',
        "Error deleting test case",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        [
            'testcase_id' => $id,
            'error' => $stmt->error
        ],
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Error deleting record: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>