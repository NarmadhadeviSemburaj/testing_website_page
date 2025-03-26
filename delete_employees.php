<?php
session_start();
include 'db_config.php';
include 'log_api.php'; // Include your logging library

// Log API access attempt
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'unknown',
    'api_access',
    "Accessed delete_employee.php",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Ensure only logged-in admin users can access
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    // Log unauthorized access attempt
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'unauthorized_access',
        "Attempted to access delete employee without admin privileges",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("Location: home.php");
    exit();
}

// Check if employee ID is provided
if (!isset($_GET['id'])) {
    // Log missing ID error
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'validation_error',
        "Employee ID not provided for deletion",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Error: Employee ID not provided.");
}

// Sanitize the employee ID
$employee_id = trim($_GET['id']);
$employee_id = $conn->real_escape_string($employee_id);

if (empty($employee_id)) {
    // Log invalid ID error
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'validation_error',
        "Invalid Employee ID provided for deletion",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['provided_id' => $_GET['id']]),
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Error: Invalid Employee ID.");
}

// Prepare the SQL statement to delete the specific employee
$sql = "DELETE FROM employees WHERE emp_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Log deletion attempt
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'delete_attempt',
        "Attempting to delete employee",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['employee_id' => $employee_id]),
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );

    // Bind the employee ID as a string
    $stmt->bind_param("s", $employee_id);

    if ($stmt->execute()) {
        // Log successful deletion
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'delete_success',
            "Employee deleted successfully",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode(['employee_id' => $employee_id]),
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        // Success: Redirect with a success message
        echo "<script>alert('Employee deleted successfully.'); window.location.href='employees.php';</script>";
    } else {
        // Log deletion failure
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'delete_failed',
            "Failed to delete employee: " . $stmt->error,
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode(['employee_id' => $employee_id, 'error' => $stmt->error]),
            500,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        // Error: Redirect with an error message
        echo "<script>alert('Failed to delete employee. Please try again.'); window.location.href='employees.php';</script>";
    }

    $stmt->close();
} else {
    // Log preparation error
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'delete_error',
        "Statement preparation failed: " . $conn->error,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['employee_id' => $employee_id, 'error' => $conn->error]),
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    // Error: Redirect with an error message
    echo "<script>alert('An error occurred. Please try again.'); window.location.href='employees.php';</script>";
}

$conn->close();
?>