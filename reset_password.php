<?php
session_start();
include 'db_config.php';
include 'log_api.php';
date_default_timezone_set('Asia/Kolkata');

// Clean up expired tokens
$conn->query("DELETE FROM password_resets WHERE expiry <= UNIX_TIMESTAMP()");

// Check if token exists in URL
if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid password reset link.";
    header("Location: forgot_password.php");
    exit();
}

$token = trim($_GET['token']);

// Validate token format
if (strlen($token) !== 100 || !ctype_xdigit($token)) {
    $_SESSION['error'] = "Invalid reset link format.";
    header("Location: forgot_password.php");
    exit();
}

// Check if token exists and isn't expired
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expiry > UNIX_TIMESTAMP()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Invalid or expired reset link.";
    header("Location: forgot_password.php");
    exit();
}

$row = $result->fetch_assoc();
$email = $row['email'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords
    if (empty($new_password)) {
        $_SESSION['error'] = "Password cannot be empty.";
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password in database
        $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Delete the used reset token
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");

            $_SESSION['success'] = "Password has been reset successfully!";
            header("Location: login.php"); // Redirect to login page
            exit();
        } else {
            $_SESSION['error'] = "Failed to update password. Please try again.";
        }
    }
    
    // If we got here, there was an error - reload the page to show it
    header("Location: " . $_SERVER['REQUEST_URI']);
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
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center mb-0">Reset Your Password</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?token=' . urlencode($token) ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>