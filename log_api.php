<?php
// log_api.php

include 'db_config.php';

function logUserAction(
    $user_id = null,
    $username,
    $action_type,
    $action_description = null,
    $endpoint = null,
    $http_method = null,
    $request_payload = null,
    $response_status = null,
    $response_data = null,
    $ip_address = null,
    $user_agent = null
) {
    global $conn;

    // Prepare the SQL query
    $sql = "INSERT INTO `log` (
                `user_id`,
                `username`,
                `action_type`,
                `action_description`,
                `endpoint`,
                `http_method`,
                `request_payload`,
                `response_status`,
                `response_data`,
                `ip_address`,
                `user_agent`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare log statement: " . $conn->error);
        return false;
    }

    // Convert JSON data to strings
    $request_payload_json = $request_payload ? json_encode($request_payload) : null;
    $response_data_json = $response_data ? json_encode($response_data) : null;

    // Bind parameters
    $stmt->bind_param(
        "ssssssssiss",
        $user_id,
        $username,
        $action_type,
        $action_description,
        $endpoint,
        $http_method,
        $request_payload_json,
        $response_status,
        $response_data_json,
        $ip_address,
        $user_agent
    );

    // Execute the query
    $result = $stmt->execute();
    if (!$result) {
        error_log("Failed to log action: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

function fetchLogs($limit = 100) {
    global $conn;

    $sql = "SELECT * FROM `log` ORDER BY `created_at` DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare fetch logs statement: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];

    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        if ($row['request_payload']) {
            $row['request_payload'] = json_decode($row['request_payload'], true);
        }
        if ($row['response_data']) {
            $row['response_data'] = json_decode($row['response_data'], true);
        }
        $logs[] = $row;
    }

    $stmt->close();
    return $logs;
}
?>