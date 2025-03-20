<?php
// log_api.php

include 'db_config.php';

/**
 * Logs user actions into the `log` table.
 *
 * @param string $user_id The ID of the user performing the action.
 * @param string $username The username of the user.
 * @param string $action_type The type of action (e.g., "login", "logout").
 * @param string $action_description Description of the action.
 * @param string $endpoint The API endpoint or page where the action occurred.
 * @param string $http_method The HTTP method used (e.g., "GET", "POST").
 * @param array|null $request_payload The request payload (if any).
 * @param int|null $response_status The HTTP response status code.
 * @param array|null $response_data The response data (if any).
 * @param string $ip_address The IP address of the user.
 * @param string $user_agent The user agent string of the user's browser.
 * @return bool Returns true if the log was successfully inserted, false otherwise.
 */
function logUserAction(
    $user_id,
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
    $stmt->close();

    return $result;
}

/**
 * Fetches logs from the `log` table.
 *
 * @param int $limit The maximum number of logs to fetch.
 * @return array Returns an array of log entries.
 */
function fetchLogs($limit = 100) {
    global $conn;

    $sql = "SELECT * FROM `log` ORDER BY `created_at` DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];

    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    $stmt->close();
    return $logs;
}
?>