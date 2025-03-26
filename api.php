<?php
session_start();
include 'db_config.php';
include 'log_api.php'; // Include the logging library

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    http_response_code(401); // Unauthorized
    
    // Log unauthorized access attempt
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'unauthorized_access',
        "Attempted to access employee API without proper privileges",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        401,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Handle GET request to fetch employee data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getEmployee') {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400); // Bad Request
        
        // Log missing ID error
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'validation_error',
            "Attempt to fetch employee without ID",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode($_GET),
            400,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
        exit();
    }

    $emp_id = $_GET['id'];
    
    // Log employee fetch attempt
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'employee_fetch',
        "Attempting to fetch employee data",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['emp_id' => $emp_id]),
        200,
        $emp_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );

    // Use Prepared Statement to fetch employee data
    $stmt = $conn->prepare("SELECT emp_id, emp_name, email, mobile_number, designation, is_admin FROM employees WHERE emp_id = ?");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if (!$employee) {
        http_response_code(404); // Not Found
        
        // Log employee not found
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'employee_not_found',
            "Requested employee not found",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode(['emp_id' => $emp_id]),
            404,
            $emp_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit();
    }

    // Log successful fetch
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'employee_fetch_success',
        "Successfully fetched employee data",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['emp_id' => $emp_id]),
        200,
        $emp_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Employee data retrieved successfully', 
        'data' => [
            'emp_id' => $employee['emp_id'],
            'emp_name' => $employee['emp_name'],
            'email' => $employee['email'],
            'mobile_number' => $employee['mobile_number'],
            'designation' => $employee['designation'],
            'is_admin' => $employee['is_admin']
        ]
    ]);
    exit();
}

// Handle POST request to update employee data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'updateEmployee') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['emp_id', 'emp_name', 'email', 'mobile_number', 'designation'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400); // Bad Request
            
            // Log missing field error
            logUserAction(
                $_SESSION['emp_id'],
                $_SESSION['user'],
                'validation_error',
                "Missing required field: $field",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                json_encode($data),
                400,
                $data['emp_id'] ?? null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
            
            echo json_encode(['status' => 'error', 'message' => "$field is required"]);
            exit();
        }
    }

    $emp_id = $data['emp_id'];
    $emp_name = $data['emp_name'];
    $email = $data['email'];
    $mobile_number = $data['mobile_number'];
    $designation = $data['designation'];
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;
    
    // Log update attempt
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'employee_update_attempt',
        "Attempting to update employee data",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode([
            'emp_id' => $emp_id,
            'changes' => [
                'emp_name' => $emp_name,
                'email' => $email,
                'mobile_number' => $mobile_number,
                'designation' => $designation,
                'is_admin' => $is_admin
            ]
        ]),
        200,
        $emp_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );

    // Check if email or mobile number already exists for another employee
    $check = $conn->prepare("SELECT emp_id FROM employees WHERE (email = ? OR mobile_number = ?) AND emp_id != ?");
    $check->bind_param("sss", $email, $mobile_number, $emp_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409); // Conflict
        
        // Log duplicate entry attempt
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'duplicate_entry',
            "Attempt to update employee with existing email or mobile number",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode([
                'email' => $email,
                'mobile_number' => $mobile_number
            ]),
            409,
            $emp_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(['status' => 'error', 'message' => 'Email or Mobile Number already exists for another employee']);
        exit();
    }

    // Use Prepared Statement to update employee data
    $stmt = $conn->prepare("UPDATE employees SET emp_name = ?, email = ?, mobile_number = ?, designation = ?, is_admin = ? WHERE emp_id = ?");
    $stmt->bind_param("ssssis", $emp_name, $email, $mobile_number, $designation, $is_admin, $emp_id);

    if ($stmt->execute()) {
        // Log successful update
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'employee_update_success',
            "Successfully updated employee data",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode(['emp_id' => $emp_id]),
            200,
            $emp_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(['status' => 'success', 'message' => 'Employee updated successfully']);
    } else {
        http_response_code(500); // Internal Server Error
        
        // Log update failure
        logUserAction(
            $_SESSION['emp_id'],
            $_SESSION['user'],
            'employee_update_failed',
            "Failed to update employee: " . $stmt->error,
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            json_encode($data),
            500,
            $emp_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        echo json_encode(['status' => 'error', 'message' => 'Failed to update employee']);
    }

    exit();
}

// Invalid request
http_response_code(400); // Bad Request

// Log invalid request
logUserAction(
    $_SESSION['emp_id'],
    $_SESSION['user'],
    'invalid_request',
    "Invalid API request received",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    json_encode(['request' => $_REQUEST]),
    400,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>