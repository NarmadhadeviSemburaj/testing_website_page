<?php
session_start();
header('Content-Type: application/json');

// Verify session and user authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die(json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'data' => null
    ]));
}

// Include database configuration
require 'db_config.php';

// Handle getEmployees action
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getEmployees') {
    try {
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT emp_id, emp_name, email, mobile_number, designation, is_admin FROM employees");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $employees = $result->fetch_all(MYSQLI_ASSOC);
        
        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Employees retrieved successfully',
            'data' => $employees,
            'count' => count($employees)
        ]);
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'data' => null
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method or missing action parameter',
        'data' => null
    ]);
}
?>