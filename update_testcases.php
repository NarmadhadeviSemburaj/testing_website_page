<?php
include 'db_config.php';
header("Content-Type: application/json"); // Set response type to JSON

$input_data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($input_data['id'])) {
        echo json_encode(["status" => "error", "message" => "Test case ID is missing!"]);
        exit();
    }

    $testcase_id = $input_data['id'];
    $bug_type = $input_data['bug_type'] ?? "Unknown"; // Default to "Unknown" if missing
    $device_name = $input_data['device_name'] ?? "Unknown";
    $android_version = $input_data['android_version'] ?? "Unknown";
    $tested_by_name = $input_data['tested_by_name'] ?? "Anonymous";
    $tested_at = $input_data['tested_at'] ?? date("Y-m-d H:i:s"); // Default to current timestamp
    $actual_result = $input_data['actual_result'] ?? "";
    $testing_result = $input_data['testing_result'] ?? "";

    // Handle File Upload (Base64)
    $file_attachment = $input_data['file_attachment'] ?? "";
    if (!empty($file_attachment)) {
        $upload_dir = "uploads/";
        $file_name = $upload_dir . "attachment_" . time() . ".png"; // Change extension as needed
        file_put_contents($file_name, base64_decode($file_attachment));
    } else {
        // Keep the existing file if no new one is uploaded
        $query = "SELECT file_attachment FROM testcase WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $testcase_id);
        $stmt->execute();
        $stmt->bind_result($existing_file);
        $stmt->fetch();
        $stmt->close();
        $file_name = $existing_file;
    }

    // Update Query
    $sql = "UPDATE testcase SET 
            bug_type=?, device_name=?, android_version=?, file_attachment=?, tested_by_name=?, 
            tested_at=?, actual_result=?, testing_result=? WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", 
        $bug_type, $device_name, $android_version, $file_name, $tested_by_name, 
        $tested_at, $actual_result, $testing_result, $testcase_id
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Test case updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update test case: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
