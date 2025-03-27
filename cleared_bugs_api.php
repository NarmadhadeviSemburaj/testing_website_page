<?php
session_start();
include 'log_api.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    logUserAction(
        null,
        'unknown',
        'unauthorized_access',
        "Attempted to access cleared bugs API without login",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        401,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["status" => "error", "message" => "Unauthorized access.", "data" => []]);
    exit();
}

include 'db_config.php';
header('Content-Type: application/json');

// Log API access
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'],
    'api_access',
    "Accessed cleared bugs API",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT * FROM bug WHERE cleared_flag = 1 ORDER BY cleared_at DESC";
        $result = $conn->query($sql);

        if ($result) {
            if ($result->num_rows > 0) {
                $cleared_bugs = $result->fetch_all(MYSQLI_ASSOC);
                
                // Log successful retrieval
                logUserAction(
                    $_SESSION['emp_id'] ?? null,
                    $_SESSION['user'],
                    'cleared_bugs_retrieved',
                    "Successfully retrieved cleared bugs",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REQUEST_METHOD'],
                    ['count' => $result->num_rows],
                    200,
                    null,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                );
                
                echo json_encode([
                    "status" => "success", 
                    "message" => "Cleared bugs retrieved successfully.", 
                    "data" => $cleared_bugs
                ]);
            } else {
                // Log empty result
                logUserAction(
                    $_SESSION['emp_id'] ?? null,
                    $_SESSION['user'],
                    'cleared_bugs_empty',
                    "No cleared bugs found",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REQUEST_METHOD'],
                    null,
                    200,
                    null,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                );
                
                echo json_encode([
                    "status" => "success", 
                    "message" => "No cleared bugs found.", 
                    "data" => []
                ]);
            }
        } else {
            // Log query error
            $error = $conn->error;
            logUserAction(
                $_SESSION['emp_id'] ?? null,
                $_SESSION['user'],
                'cleared_bugs_error',
                "Database query failed",
                $_SERVER['REQUEST_URI'],
                $_SERVER['REQUEST_METHOD'],
                ['error' => $error],
                500,
                null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
            
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode([
                "status" => "error", 
                "message" => "Database error: " . $error, 
                "data" => []
            ]);
        }
    } catch (Exception $e) {
        // Log exception
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'cleared_bugs_exception',
            "Exception occurred while fetching cleared bugs",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['exception' => $e->getMessage()],
            500,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode([
            "status" => "error", 
            "message" => "Exception: " . $e->getMessage(), 
            "data" => []
        ]);
    }
} else {
    // Log invalid method
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'invalid_method',
        "Invalid request method for cleared bugs API",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        405,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method.", 
        "data" => []
    ]);
}

// Close database connection
$conn->close();
exit();
?>