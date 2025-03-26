<?php
session_start();
include 'db_config.php';
header("Content-Type: application/json");

$response = ['status' => 'error', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Bug ID is required");
    }

    $bugId = $conn->real_escape_string($_POST['id']);
    $testcaseId = isset($_POST['testcase_id']) ? $conn->real_escape_string($_POST['testcase_id']) : null;
    $clearedBy = $_SESSION['user'] ?? 'Unknown';

    // Prepare the SQL query
    $sql = "UPDATE bug SET cleared_flag = 1, cleared_by = ?, cleared_at = NOW() WHERE id = ?";
    $types = "ss";
    $params = [$clearedBy, $bugId];

    if ($testcaseId) {
        $sql .= " AND testcase_id = ?";
        $types .= "s";
        $params[] = $testcaseId;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No bug found with the specified ID");
    }

    $response = [
        'status' => 'success',
        'message' => 'Bug cleared successfully',
        'bug_id' => $bugId
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
    echo json_encode($response);
}
?>