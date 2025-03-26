<?php
header('Content-Type: application/json');
session_start();

// Include logging functions
include 'log_api.php';

// Log API request initiation
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'submit_testcase_request',
    "Submit testcase API called",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log received data (truncated for sensitive fields)
$logData = $data ? [
    'id' => $data['id'] ?? null,
    'product_name' => $data['product_name'] ?? null,
    'version' => $data['version'] ?? null,
    'module_name' => $data['module_name'] ?? null,
    'description' => isset($data['description']) ? substr($data['description'], 0, 100) . '...' : null,
    'test_steps' => isset($data['test_steps']) ? substr($data['test_steps'], 0, 100) . '...' : null
] : null;

logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'submit_testcase_data',
    "Received testcase data",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    $logData,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Validate input
if (!isset($data['module_name']) || !isset($data['description'])) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'submit_testcase_validation',
        "Required fields missing",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['missing_fields' => array_diff(['module_name', 'description'], array_keys($data ?? []))],
        400,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'submit_testcase_db_error',
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

// Escape all inputs
$id = isset($data['id']) ? $conn->real_escape_string($data['id']) : null;
$products = array_map([$conn, 'real_escape_string'], $data['product_name']);
$version = $conn->real_escape_string($data['version']);
$module = $conn->real_escape_string($data['module_name']);
$desc = $conn->real_escape_string($data['description']);
$preconditions = $conn->real_escape_string($data['preconditions'] ?? '');
$steps = $conn->real_escape_string($data['test_steps']);
$results = $conn->real_escape_string($data['expected_results']);

$product = $products[0]; // Or implement multi-product support

if ($id) {
    $sql = "UPDATE testcase SET 
            Product_name = '$product',
            Version = '$version',
            Module_name = '$module',
            description = '$desc',
            preconditions = '$preconditions',
            test_steps = '$steps',
            expected_results = '$results'";
            
    if (isset($data['testing_result'])) {
        $testing_result = $conn->real_escape_string($data['testing_result']);
        $sql .= ", testing_result = '$testing_result'";
    }
    
    $sql .= " WHERE id = '$id'";
    $action = 'update';
} else {
    $sql = "INSERT INTO testcase 
            (Product_name, Version, Module_name, description, 
             preconditions, test_steps, expected_results, testing_result)
            VALUES 
            ('$product', '$version', '$module', '$desc', 
             '$preconditions', '$steps', '$results', NULL)";
    $action = 'create';
}

// Log the query execution
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'submit_testcase_query',
    "Executing $action query",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    ['action' => $action, 'testcase_id' => $id],
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

if ($conn->query($sql)) {
    $message = "Test case saved successfully";
    
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'submit_testcase_success',
        $message,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['action' => $action, 'testcase_id' => $id ?: $conn->insert_id],
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(['status' => 'success', 'message' => $message]);
} else {
    $message = "Error saving test case: " . $conn->error;
    
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'api_user',
        'submit_testcase_failed',
        $message,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        ['error' => $conn->error, 'action' => $action, 'testcase_id' => $id],
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    echo json_encode(['status' => 'error', 'message' => $message]);
}

// Log API request completion
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'] ?? 'api_user',
    'submit_testcase_complete',
    "Submit testcase API completed",
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