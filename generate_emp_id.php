<?php
session_start();
include 'db_config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Ensure only logged-in admin users can access
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'data' => null
    ]);
    exit();
}

// Fetch the last emp_id from the database
$sql = "SELECT emp_id FROM employees ORDER BY emp_id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_emp_id = $row['emp_id'];
    $last_number = intval(substr($last_emp_id, 4)); // Extract the numeric part
    $new_number = $last_number + 1;
    $new_emp_id = 'EMP_' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
} else {
    // If no employees exist, start with EMP_0001
    $new_emp_id = 'EMP_0001';
}

echo json_encode([
    'status' => 'success',
    'emp_id' => $new_emp_id
]);

$conn->close();
?>