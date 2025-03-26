<?php
include 'db_config.php'; // Ensure this connects to your database

function logAction($user_id, $username, $action_type, $action_description, $endpoint, $http_method, $request_payload, $response_status, $response_data) {
    global $conn;

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $request_payload_json = json_encode($request_payload);
    $response_data_json = json_encode($response_data);

    $sql = "INSERT INTO log (user_id, username, action_type, action_description, endpoint, http_method, request_payload, response_status, response_data, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssiiss", $user_id, $username, $action_type, $action_description, $endpoint, $http_method, $request_payload_json, $response_status, $response_data_json, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

// Example Usage
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'] ?? null;
    $username = $_POST['username'] ?? null;
    $action_type = $_POST['action_type'] ?? null;
    $action_description = $_POST['action_description'] ?? null;
    $endpoint = $_POST['endpoint'] ?? $_SERVER['REQUEST_URI'];
    $http_method = $_POST['http_method'] ?? $_SERVER['REQUEST_METHOD'];
    $request_payload = $_POST['request_payload'] ?? [];
    $response_status = $_POST['response_status'] ?? 200;
    $response_data = $_POST['response_data'] ?? [];

    if (logAction($user_id, $username, $action_type, $action_description, $endpoint, $http_method, $request_payload, $response_status, $response_data)) {
        echo json_encode(["success" => true, "message" => "Log stored successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Log storage failed"]);
    }
}
?>
