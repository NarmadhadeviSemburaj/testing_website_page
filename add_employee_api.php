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

// Get the raw JSON input
$input_data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$required_fields = ['emp_name', 'email', 'mobile_number', 'designation', 'password'];
foreach ($required_fields as $field) {
    if (empty($input_data[$field])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => "Missing required field: $field",
            'data' => null
        ]);
        exit();
    }
}

// Generate the new emp_id
$sql = "SELECT emp_id FROM employees ORDER BY emp_id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_emp_id = $row['emp_id'];
    $last_number = intval(substr($last_emp_id, 4)); // Extract the numeric part
    $new_number = $last_number + 1;
    $emp_id = 'EMP_' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
} else {
    // If no employees exist, start with EMP_0001
    $emp_id = 'EMP_0001';
}

// Assign values
$emp_name = trim($input_data['emp_name']);
$email = trim($input_data['email']);
$mobile_number = trim($input_data['mobile_number']);
$designation = trim($input_data['designation']);
$password = trim($input_data['password']);
$is_admin = isset($input_data['is_admin']) ? (int)$input_data['is_admin'] : 0; // Ensure is_admin is 0 if not set

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email or mobile number already exists
$check = "SELECT * FROM employees WHERE email=? OR mobile_number=?";
$stmt = $conn->prepare($check);
$stmt->bind_param("ss", $email, $mobile_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode([
        'status' => 'error',
        'message' => 'Email or Mobile Number already exists',
        'data' => null
    ]);
    exit();
}

// Insert new employee
$sql = "INSERT INTO employees (emp_id, emp_name, email, mobile_number, designation, password, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", $emp_id, $emp_name, $email, $mobile_number, $designation, $hashed_password, $is_admin);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Employee added successfully',
        'data' => [
            'emp_id' => $emp_id,
            'emp_name' => $emp_name,
            'email' => $email,
            'mobile_number' => $mobile_number,
            'designation' => $designation,
            'is_admin' => $is_admin
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $stmt->error,
        'data' => null
    ]);
}

$stmt->close();
?>