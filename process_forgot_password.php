<?php
session_start();
include 'db_config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            $_SESSION['success'] = "Password reset link sent to your email.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = "No account found with that email.";
    }
    header("Location: forgot_password.php");
    exit();
}
?>


