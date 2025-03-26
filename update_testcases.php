<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debugging Step: Print POST data and check if testing_result exists
    if (!isset($_POST['testing_result'])) {
        die(json_encode(['success' => false, 'message' => 'Missing field: testing_result']));
    }

    $testcase_id = $_POST['id'];
    $device_name = $_POST['device_name'];
    $android_version = $_POST['android_version'];
    $tested_by_name = $_POST['tested_by_name'];
    $tested_at = $_POST['tested_at'];
    $testing_result = trim($_POST['testing_result']); // Ensure value is trimmed properly
    
    // If testing_result is 'Fail', actual_result and bug_type are required
    $actual_result = ($testing_result === 'Fail') ? $_POST['actual_result'] : null;
    $bug_type = ($testing_result === 'Fail') ? $_POST['bug_type'] : null;

    // Handle File Upload
    $file_attachment = "";
    if (!empty($_FILES['file_attachment']['name'])) {
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_attachment = $upload_dir . basename($_FILES["file_attachment"]["name"]);
        move_uploaded_file($_FILES["file_attachment"]["tmp_name"], $file_attachment);
    } else {
        $query = "SELECT file_attachment FROM testcase WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $testcase_id);
            $stmt->execute();
            $stmt->bind_result($existing_file);
            $stmt->fetch();
            $stmt->close();
            $file_attachment = $existing_file;
        }
    }

    // Update testcase with testing_result
    $sql = "UPDATE testcase SET 
            device_name=?, android_version=?, file_attachment=?, tested_by_name=?, 
            tested_at=?, testing_result=?, actual_result=?, bug_type=? 
            WHERE id=? LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]));
    }

    $stmt->bind_param("ssssssssi", 
        $device_name, $android_version, $file_attachment, $tested_by_name, 
        $tested_at, $testing_result, $actual_result, $bug_type, $testcase_id
    );

    $success = $stmt->execute();
    if (!$success) {
        die(json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]));
    }

    // Insert into bug table if test case failed
    if ($testing_result == 'Fail') {
        $sql_bug = "INSERT INTO bug (testcase_id, bug_type, device_name, android_version, tested_by_name, tested_at, actual_result, testing_result, file_attachment, Module_name, description, Product_name, Version, created_at, tested_by_id, precondition, test_steps, expected_results) 
                     SELECT id, bug_type, device_name, android_version, tested_by_name, tested_at, actual_result, testing_result, file_attachment, Module_name, description, Product_name, Version, NOW(), tested_by_id, preconditions, test_steps, expected_results 
                     FROM testcase 
                     WHERE id = ? LIMIT 1";

        $stmt_bug = $conn->prepare($sql_bug);
        if ($stmt_bug) {
            $stmt_bug->bind_param("i", $testcase_id);
            $stmt_bug->execute();
            $stmt_bug->close();
        } else {
            error_log("Bug table insert failed: " . $conn->error);
        }
    }

    if ($isAjax) {
        $response = ['success' => $success, 'message' => 'Test case updated successfully', 'next_id' => 0];

        if ($success) {
            $product_name = $_POST['product_name'] ?? '';
            $version = $_POST['version'] ?? '';

            $sql_next = "SELECT id FROM testcase WHERE Product_name = ? AND Version = ? AND id > ? ORDER BY id ASC LIMIT 1";
            $stmt_next = $conn->prepare($sql_next);
            if ($stmt_next) {
                $stmt_next->bind_param("ssi", $product_name, $version, $testcase_id);
                $stmt_next->execute();
                $stmt_next->bind_result($next_id);
                if ($stmt_next->fetch()) {
                    $response['next_id'] = $next_id;
                }
                $stmt_next->close();
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        if ($success) {
            echo "<script>alert('Test case updated successfully'); window.location.href='update_tc3.php';</script>";
        } else {
            echo "Error updating test case.";
        }
    }

    $stmt->close();
}
$conn->close();
?>
