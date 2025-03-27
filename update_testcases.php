<?php
include 'db_config.php';
include 'log_api.php'; // Include logging functionality
header("Content-Type: application/json");

// Start session
session_start();

// Initialize logging context
$logContext = [
    'endpoint' => $_SERVER['REQUEST_URI'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['user'] ?? 'unknown'
];

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $files = $_FILES ?? [];

    // Log request details (sanitized input)
    $sanitizedInput = $input;
    if (isset($sanitizedInput['file_attachment'])) unset($sanitizedInput['file_attachment']);
    logAction(
        'API_REQUEST',
        "Processing test case update request",
        array_merge($logContext, ['input' => $sanitizedInput])
    );

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logAction(
            'API_ERROR',
            "Invalid request method",
            array_merge($logContext, ['error' => 'Only POST requests allowed'])
        );
        throw new Exception("Only POST requests allowed", 405);
    }

    if (empty($input['id'])) {
        logAction(
            'API_ERROR',
            "Missing test case ID",
            array_merge($logContext, ['error' => 'Test case ID required'])
        );
        throw new Exception("Test case ID required", 400);
    }

    // Prepare variables
    $testcaseId = $input['id'];
    $testingResult = $input['testing_result'] ?? '';
    $isFailure = ($testingResult === 'fail');
    
    // Handle file upload
    $filePath = null;
    if (!empty($files['file_attachment']['name']) && $files['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/bugs/";
        
        logAction(
            'FILE_UPLOAD',
            "Attempting file upload",
            array_merge($logContext, [
                'file_name' => $files['file_attachment']['name'],
                'file_size' => $files['file_attachment']['size']
            ])
        );

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                logAction(
                    'FILE_ERROR',
                    "Failed to create upload directory",
                    array_merge($logContext, ['directory' => $uploadDir])
                );
                throw new Exception("Failed to create upload directory", 500);
            }
        }
        
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', basename($files['file_attachment']['name']));
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($files['file_attachment']['tmp_name'], $targetPath)) {
            $filePath = $targetPath;
            logAction(
                'FILE_SUCCESS',
                "File uploaded successfully",
                array_merge($logContext, [
                    'file_path' => $targetPath,
                    'file_size' => filesize($targetPath)
                ])
            );
        } else {
            logAction(
                'FILE_ERROR',
                "File upload failed",
                array_merge($logContext, ['error' => $files['file_attachment']['error']])
            );
            throw new Exception("File upload failed", 500);
        }
    }

    // Begin transaction
    $conn->begin_transaction();
    logAction(
        'DB_TRANSACTION',
        "Transaction started",
        array_merge($logContext, ['testcase_id' => $testcaseId])
    );

    try {
        // 1. Update test case
        $updateSql = "UPDATE testcase SET 
            bug_type = ?, 
            device_name = ?, 
            android_version = ?,
            file_attachment = COALESCE(?, file_attachment),
            tested_by_name = ?, 
            tested_at = NOW(),
            actual_result = ?, 
            testing_result = ?
            WHERE id = ?";
        
        $stmt = $conn->prepare($updateSql);
        if (!$stmt) {
            $error = $conn->error;
            logAction(
                'DB_ERROR',
                "Prepare failed for test case update",
                array_merge($logContext, [
                    'error' => $error,
                    'sql' => $updateSql,
                    'testcase_id' => $testcaseId
                ])
            );
            throw new Exception("Prepare failed: " . $error);
        }
        
        // Bind parameters
        $bugType = $input['bug_type'] ?? 'Unknown';
        $deviceName = $input['device_name'] ?? 'Unknown';
        $androidVersion = $input['android_version'] ?? 'Unknown';
        $testedByName = $input['tested_by_name'] ?? 'Anonymous';
        $actualResult = $input['actual_result'] ?? '';
        
        $stmt->bind_param("ssssssss", 
            $bugType,
            $deviceName,
            $androidVersion,
            $filePath,
            $testedByName,
            $actualResult,
            $testingResult,
            $testcaseId
        );
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            logAction(
                'DB_ERROR',
                "Test case update failed",
                array_merge($logContext, [
                    'error' => $error,
                    'testcase_id' => $testcaseId
                ])
            );
            throw new Exception("Test case update failed: " . $error);
        }
        $stmt->close();

        logAction(
            'TESTCASE_UPDATE',
            "Test case updated successfully",
            array_merge($logContext, [
                'testcase_id' => $testcaseId,
                'result' => $testingResult,
                'bug_type' => $bugType
            ])
        );

        // 2. Create bug report if test failed
        $bugId = null;
        if ($isFailure) {
            // Get test case details
            $stmt = $conn->prepare("SELECT 
                Module_name, description, Product_name, Version,
                test_steps, expected_results, precondition
                FROM testcase WHERE id = ?");
            $stmt->bind_param("s", $testcaseId);
            $stmt->execute();
            $result = $stmt->get_result();
            $testcaseData = $result->fetch_assoc();
            $stmt->close();
            
            if (!$testcaseData) {
                logAction(
                    'DB_ERROR',
                    "Test case details not found",
                    array_merge($logContext, ['testcase_id' => $testcaseId])
                );
                throw new Exception("Could not fetch test case details");
            }

            $testedById = $_SESSION['user_id'] ?? null;

            // Insert bug report
            $bugSql = "INSERT INTO bug (
                testcase_id, bug_type, device_name, android_version,
                tested_by_name, tested_at, actual_result, testing_result, file_attachment,
                Module_name, description, Product_name, Version, tested_by_id,
                precondition, test_steps, expected_results, cleared_flag
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
            
            $stmt = $conn->prepare($bugSql);
            if (!$stmt) {
                $error = $conn->error;
                logAction(
                    'DB_ERROR',
                    "Prepare failed for bug creation",
                    array_merge($logContext, [
                        'error' => $error,
                        'sql' => $bugSql
                    ])
                );
                throw new Exception("Prepare failed: " . $error);
            }
            
            $stmt->bind_param("ssssssssssssssss", 
                $testcaseId,
                $bugType,
                $deviceName,
                $androidVersion,
                $testedByName,
                $actualResult,
                $testingResult,
                $filePath,
                $testcaseData['Module_name'],
                $testcaseData['description'],
                $testcaseData['Product_name'],
                $testcaseData['Version'],
                $testedById,
                $testcaseData['precondition'],
                $testcaseData['test_steps'],
                $testcaseData['expected_results']
            );
            
            if (!$stmt->execute()) {
                $error = $stmt->error;
                logAction(
                    'DB_ERROR',
                    "Bug creation failed",
                    array_merge($logContext, [
                        'error' => $error,
                        'testcase_id' => $testcaseId
                    ])
                );
                throw new Exception("Bug creation failed: " . $error);
            }
            
            $bugId = $conn->insert_id;
            $stmt->close();

            logAction(
                'BUG_CREATED',
                "Bug report created",
                array_merge($logContext, [
                    'bug_id' => $bugId,
                    'testcase_id' => $testcaseId,
                    'bug_type' => $bugType
                ])
            );
        }

        // Commit transaction
        $conn->commit();
        logAction(
            'TRANSACTION_SUCCESS',
            "Transaction completed successfully",
            array_merge($logContext, [
                'testcase_id' => $testcaseId,
                'bug_created' => $isFailure,
                'bug_id' => $bugId
            ])
        );
        
        // Return response
        $response = [
            'status' => 'success',
            'message' => $isFailure ? 'Test failed - bug reported' : 'Test case updated'
        ];
        if ($isFailure) $response['bug_id'] = $bugId;
        
        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        logAction(
            'TRANSACTION_FAILED',
            "Transaction rolled back",
            array_merge($logContext, [
                'error' => $e->getMessage(),
                'testcase_id' => $testcaseId
            ])
        );
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    logAction(
        'API_EXCEPTION',
        "API exception occurred",
        array_merge($logContext, [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ])
    );
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) $conn->close();

/**
 * Helper function for consistent logging
 */
function logAction($actionType, $message, $context = []) {
    // Sanitize sensitive data in context
    if (isset($context['input']['password'])) {
        $context['input']['password'] = '*****';
    }
    if (isset($context['input']['token'])) {
        $context['input']['token'] = '*****';
    }
    
    logUserAction(
        $context['user_id'] ?? null,
        $context['username'] ?? 'unknown',
        $actionType,
        $message,
        $context['endpoint'] ?? '',
        $context['method'] ?? '',
        $context,
        $context['code'] ?? 200,
        null,
        $context['ip'] ?? '',
        $context['user_agent'] ?? ''
    );
}