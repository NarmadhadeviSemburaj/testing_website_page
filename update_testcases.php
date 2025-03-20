<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $testcase_id = $_POST['id'];
    $bug_type = $_POST['bug_type'];
    $device_name = $_POST['device_name'];
    $android_version = $_POST['android_version'];
    $tested_by_name = $_POST['tested_by_name'];
    $tested_at = $_POST['tested_at'];
    $actual_result = $_POST['actual_result'];
    $testing_result = $_POST['testing_result'];
    
    // For AJAX requests, we need to handle file uploads differently
    $file_attachment = "";
    if (!empty($_FILES['file_attachment']['name'])) {
        $upload_dir = "uploads/";
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_attachment = $upload_dir . basename($_FILES["file_attachment"]["name"]);
        move_uploaded_file($_FILES["file_attachment"]["tmp_name"], $file_attachment);
    } else {
        // If no new file uploaded, keep the existing file
        $query = "SELECT file_attachment FROM testcase WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $testcase_id);
        $stmt->execute();
        $stmt->bind_result($existing_file);
        $stmt->fetch();
        $stmt->close();
        $file_attachment = $existing_file;
    }
    
    // Update the testcase table
    $sql = "UPDATE testcase SET 
            bug_type=?, device_name=?, android_version=?, file_attachment=?, tested_by_name=?, 
            tested_at=?, actual_result=?, testing_result=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", 
        $bug_type, $device_name, $android_version, $file_attachment, $tested_by_name, 
        $tested_at, $actual_result, $testing_result, $testcase_id
    );
    
    $success = $stmt->execute();
    
    // If the test case is marked as "Fail", insert a new record into the bug table
    if ($success && $testing_result == 'Fail') {
        // Fetch precondition, test_steps, and expected_results from the testcase table
        $sql_bug = "INSERT INTO bug (testcase_id, bug_type, device_name, android_version, tested_by_name, tested_at, actual_result, testing_result, file_attachment, Module_name, description, Product_name, Version, created_at, tested_by_id, precondition, test_steps, expected_results) 
                     SELECT id, bug_type, device_name, android_version, tested_by_name, tested_at, actual_result, testing_result, file_attachment, Module_name, description, Product_name, Version, NOW(), tested_by_id, preconditions, test_steps, expected_results 
                     FROM testcase 
                     WHERE id = ?";
        $stmt_bug = $conn->prepare($sql_bug);
        $stmt_bug->bind_param("i", $testcase_id);
        $stmt_bug->execute();
        $stmt_bug->close();
    }
    
    // Return appropriate response based on request type
    if ($isAjax) {
        $response = array(
            'success' => $success,
            'message' => $success ? 'Test case updated successfully' : 'Error updating test case: ' . $stmt->error,
            'next_id' => 0
        );
        
        // Find the next testcase ID if successful
        if ($success) {
            // Get the current product and version filters from the session
            $product_name = $_POST['product_name'] ?? '';
            $version = $_POST['version'] ?? '';
            
            // Find the next testcase ID
            $sql_next = "SELECT id FROM testcase 
                         WHERE Product_name = ? AND Version = ? AND id > ? 
                         ORDER BY id ASC LIMIT 1";
            $stmt_next = $conn->prepare($sql_next);
            $stmt_next->bind_param("ssi", $product_name, $version, $testcase_id);
            $stmt_next->execute();
            $stmt_next->bind_result($next_id);
            if ($stmt_next->fetch()) {
                $response['next_id'] = $next_id;
            }
            $stmt_next->close();
        }
        
        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Traditional response for non-AJAX requests
        if ($success) {
            echo "<script>alert('Test case updated successfully'); window.location.href='update_tc3.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
    
    $stmt->close();
}
$conn->close();
?>