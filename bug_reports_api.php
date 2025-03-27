<?php
session_start();
include 'db_config.php';
include 'log_api.php';
header("Content-Type: application/json");

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred',
    'bug_id' => null
];

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST is allowed.");
    }

    // Validate required parameters
    if (!isset($_POST['action']) || $_POST['action'] !== 'clear_bug') {
        throw new Exception("Invalid action specified");
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Bug ID is required");
    }

    // Sanitize inputs
    $bugId = $conn->real_escape_string($_POST['id']);
    $testcaseId = isset($_POST['testcase_id']) ? $conn->real_escape_string($_POST['testcase_id']) : null;
    $clearedBy = isset($_SESSION['user']) ? $_SESSION['user'] : 'System';

    // Prepare the SQL query
    $sql = "UPDATE bug SET 
            cleared_flag = 1, 
            cleared_by = ?, 
            cleared_at = NOW() 
            WHERE id = ?";
    
    $types = "si";
    $params = [$clearedBy, $bugId];

    // Add testcase_id condition if provided
    if ($testcaseId) {
        $sql .= " AND testcase_id = ?";
        $types .= "s";
        $params[] = $testcaseId;
    }

    // Execute the query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }

    // Check if any rows were affected
    if ($stmt->affected_rows === 0) {
        throw new Exception("No bug found with the specified ID or it was already cleared");
    }

    // Success response
    $response = [
        'status' => 'success',
        'message' => 'Bug cleared successfully',
        'bug_id' => $bugId,
        'cleared_by' => $clearedBy,
        'cleared_at' => date('Y-m-d H:i:s')
    ];

    // Log the successful operation
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $clearedBy,
        'bug_cleared',
        "Bug marked as cleared",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        $response,
        200,
        $bugId,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );

} catch (Exception $e) {
    // Error response
    $response['message'] = $e->getMessage();
    
    // Log the error
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'system',
        'bug_clear_error',
        "Error clearing bug",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        [
            'error' => $e->getMessage(),
            'bug_id' => $bugId ?? null,
            'testcase_id' => $testcaseId ?? null
        ],
        400,
        $bugId ?? null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
} finally {
    // Close connections
    if (isset($stmt)) $stmt->close();
    $conn->close();
    
    // Ensure no output before this
    echo json_encode($response);
    exit();
}