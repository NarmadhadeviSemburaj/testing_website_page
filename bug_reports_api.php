<?php
session_start(); // Start the session
include 'db_config.php';
header("Content-Type: application/json"); // Set response type to JSON

$response = ["status" => "error", "message" => "Invalid request.", "data" => null];

// Handle "Clear" button action (AJAX request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    if (isset($input['id'])) {
        $id = intval($input['id']);

        // Get the session user (assuming it's stored in $_SESSION['user'])
        $cleared_by = isset($_SESSION['user']) ? $_SESSION['user'] : 'Unknown';

        // Prepare statement to update the testcase
        $sql = "UPDATE testcase 
                SET testing_result = 'Pass', 
                    bug_type = NULL, 
                    result_changed_at = NOW(), 
                    cleared_flag = 1, 
                    cleared_by = ? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $cleared_by, $id);

            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Test case cleared successfully.", "data" => null];
            } else {
                $response = ["status" => "error", "message" => "Failed to execute SQL statement: " . $stmt->error];
            }

            $stmt->close();
        } else {
            $response = ["status" => "error", "message" => "Failed to prepare SQL statement: " . $conn->error];
        }
    } else {
        $response = ["status" => "error", "message" => "Test case ID is missing."];
    }
} else {
    $response = ["status" => "error", "message" => "Invalid request method."];
}

echo json_encode($response);
exit;
?>