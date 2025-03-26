<?php
$conn = new mysqli("localhost", "root", "", "testing_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;

// Validate the ID as a non-empty string
if (!$id || !is_string($id)) {
    die("Invalid or missing ID");
}

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM testcase WHERE id = ?");
$stmt->bind_param("s", $id); // Use "s" for string type

if ($stmt->execute()) {
    header("Location: index1.php");
    exit();
} else {
    die("Error deleting record: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>