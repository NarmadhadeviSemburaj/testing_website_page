<?php
session_start();
include 'db_config.php';
include 'log_api.php'; // Include the logging function
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $ip_address = $_SERVER['REMOTE_ADDR']; // Get user's IP address
    $user_agent = $_SERVER['HTTP_USER_AGENT']; // Get user's browser agent

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Log invalid email error
        logUserAction(
            null, // user_id (not logged in)
            $email, // username or email
            'forgot_password_request_failed', // action_type
            'Invalid email address provided', // action_description
            '/process_forgot_password.php', // endpoint
            'POST', // http_method
            ['email' => $email], // request_payload
            400, // response_status (Bad Request)
            ['error' => 'Invalid email address'], // response_data
            $ip_address, // ip_address
            $user_agent // user_agent
        );

        $_SESSION['error'] = "Invalid email address.";
        header("Location: forgot_password.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $token = bin2hex(random_bytes(50));
        $expiry = time() + 1800; // Token expires in 30 minutes

        $conn->query("DELETE FROM password_resets WHERE email = '$email'");

        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $email, $token, $expiry);
        $stmt->execute();

        $reset_link = "http://localhost/testing_website/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'rsubhashinisubha004@gmail.com';
            $mail->Password = 'mbhi gsjg hgio lhzk';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'Test Management System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the link below to reset your password:<br><a href='$reset_link'>$reset_link</a>";

            $mail->send();

            // Log successful password reset request
            logUserAction(
                null, // user_id (not logged in)
                $email, // username or email
                'forgot_password_request', // action_type
                'Password reset link sent successfully', // action_description
                '/process_forgot_password.php', // endpoint
                'POST', // http_method
                ['email' => $email], // request_payload
                200, // response_status (Success)
                ['message' => 'Password reset link sent'], // response_data
                $ip_address, // ip_address
                $user_agent // user_agent
            );

            $_SESSION['success'] = "Password reset link sent to your email.";
        } catch (Exception $e) {
            // Log email sending failure
            logUserAction(
                null, // user_id (not logged in)
                $email, // username or email
                'forgot_password_request_failed', // action_type
                'Failed to send password reset email', // action_description
                '/process_forgot_password.php', // endpoint
                'POST', // http_method
                ['email' => $email], // request_payload
                500, // response_status (Internal Server Error)
                ['error' => $mail->ErrorInfo], // response_data
                $ip_address, // ip_address
                $user_agent // user_agent
            );

            $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        }
    } else {
        // Log no account found error
        logUserAction(
            null, // user_id (not logged in)
            $email, // username or email
            'forgot_password_request_failed', // action_type
            'No account found with the provided email', // action_description
            '/process_forgot_password.php', // endpoint
            'POST', // http_method
            ['email' => $email], // request_payload
            404, // response_status (Not Found)
            ['error' => 'No account found with that email'], // response_data
            $ip_address, // ip_address
            $user_agent // user_agent
        );

        $_SESSION['error'] = "No account found with that email.";
    }
    header("Location: forgot_password.php");
    exit();
}
?>