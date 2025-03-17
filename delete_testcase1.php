<?php
$conn = new mysqli("localhost", "root", "", "testing_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid or missing ID");
}

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM testcase WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index1.php");
    exit();
} else {
    die("Error deleting record: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
