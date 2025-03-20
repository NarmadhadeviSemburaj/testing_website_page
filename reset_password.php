<?php
session_start();
include 'db_config.php';
include 'log_api.php'; // Include the logging function

if (!isset($_GET['token'])) {
    // Log invalid password reset link attempt
    logUserAction(
        null, // user_id (not logged in)
        null, // username or email
        'reset_password_failed', // action_type
        'Invalid password reset link (no token provided)', // action_description
        '/reset_password.php', // endpoint
        'GET', // http_method
        null, // request_payload
        400, // response_status (Bad Request)
        ['error' => 'Invalid password reset link'], // response_data
        $_SERVER['REMOTE_ADDR'], // ip_address
        $_SERVER['HTTP_USER_AGENT'] // user_agent
    );

    $_SESSION['error'] = "Invalid password reset link.";
    header("Location: forgot_password.php");
    exit();
}

$token = $_GET['token'];
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expiry > UNIX_TIMESTAMP()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Log invalid or expired token attempt
    logUserAction(
        null, // user_id (not logged in)
        null, // username or email
        'reset_password_failed', // action_type
        'Invalid or expired token provided', // action_description
        '/reset_password.php', // endpoint
        'GET', // http_method
        ['token' => $token], // request_payload
        400, // response_status (Bad Request)
        ['error' => 'Invalid or expired token'], // response_data
        $_SERVER['REMOTE_ADDR'], // ip_address
        $_SERVER['HTTP_USER_AGENT'] // user_agent
    );

    $_SESSION['error'] = "Invalid or expired token.";
    header("Location: forgot_password.php");
    exit();
}

$row = $result->fetch_assoc();
$email = $row['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['password'];
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update the password in the database
    $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    $stmt->execute();

    // Delete the reset token
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Log successful password reset
    logUserAction(
        null, // user_id (not logged in)
        $email, // username or email
        'reset_password_success', // action_type
        'Password reset successfully', // action_description
        '/reset_password.php', // endpoint
        'POST', // http_method
        ['email' => $email], // request_payload
        200, // response_status (Success)
        ['message' => 'Password reset successfully'], // response_data
        $_SERVER['REMOTE_ADDR'], // ip_address
        $_SERVER['HTTP_USER_AGENT'] // user_agent
    );

    $_SESSION['success'] = "Password has been reset successfully.";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h3 class="text-center">Reset Password</h3>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>
    </div>
</div>
</body>
</html>