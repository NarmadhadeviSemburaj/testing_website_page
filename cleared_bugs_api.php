<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["status" => "error", "message" => "Unauthorized access.", "data" => []]);
    exit();
}

include 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM testcase WHERE cleared_flag = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $cleared_bugs = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(["status" => "success", "message" => "Cleared bugs retrieved successfully.", "data" => $cleared_bugs]);
    } else {
        echo json_encode(["status" => "success", "message" => "No cleared bugs found.", "data" => []]);
    }
    
    $conn->close(); // Ensure the database connection is closed
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid request method.", "data" => []]);
$conn->close(); // Close connection before exiting
exit();
?>
