<?php
session_start();
include 'db_config.php';

// Ensure only logged-in admin users can access
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    header("Location: home.php");
    exit();
}

// Check if employee ID is provided
if (!isset($_GET['id'])) {
    die("Error: Employee ID not provided.");
}

// Sanitize the employee ID (since it's a VARCHAR, no need to convert to int)
$employee_id = trim($_GET['id']); // Trim whitespace
$employee_id = $conn->real_escape_string($employee_id); // Sanitize to prevent SQL injection

if (empty($employee_id)) {
    die("Error: Invalid Employee ID.");
}

// Prepare the SQL statement to delete the specific employee
$sql = "DELETE FROM employees WHERE emp_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind the employee ID as a string (since emp_id is VARCHAR)
    $stmt->bind_param("s", $employee_id);

    // Debugging: Log the employee ID and query
    error_log("Attempting to delete Employee ID: " . $employee_id);
    error_log("SQL Query: " . $sql);

    if ($stmt->execute()) {
        // Success: Redirect with a success message
        echo "<script>alert('Employee deleted successfully.'); window.location.href='employees.php';</script>";
    } else {
        // Error: Redirect with an error message
        echo "<script>alert('Failed to delete employee. Please try again.'); window.location.href='employees.php';</script>";
    }

    $stmt->close(); // Close the statement
} else {
    // Error: Redirect with an error message
    echo "<script>alert('An error occurred. Please try again.'); window.location.href='employees.php';</script>";
}

$conn->close();
?>