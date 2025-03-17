<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Handle GET request to fetch employee data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getEmployee') {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
        exit();
    }

    $emp_id = $_GET['id'];

    // Use Prepared Statement to fetch employee data
    $stmt = $conn->prepare("SELECT * FROM employees WHERE emp_id = ?");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if (!$employee) {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Employee data retrieved successfully', 'data' => $employee]);
    exit();
}

// Handle POST request to update employee data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'updateEmployee') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['emp_id']) || empty($data['emp_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
        exit();
    }

    $emp_id = $data['emp_id'];
    $emp_name = $data['emp_name'];
    $email = $data['email'];
    $mobile_number = $data['mobile_number'];
    $designation = $data['designation'];
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0; // Ensure is_admin is 0 if not set

    // Use Prepared Statement to update employee data
    $stmt = $conn->prepare("UPDATE employees SET emp_name = ?, email = ?, mobile_number = ?, designation = ?, is_admin = ? WHERE emp_id = ?");
    $stmt->bind_param("ssssis", $emp_name, $email, $mobile_number, $designation, $is_admin, $emp_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Employee updated successfully']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Failed to update employee']);
    }

    exit();
}

// Invalid request
http_response_code(400); // Bad Request
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>