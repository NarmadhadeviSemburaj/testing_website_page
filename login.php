<?php
session_start();
if (isset($_SESSION['user'])) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['last_activity'] = time();
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Test Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.7.8/lottie.min.js"></script>
    <style>
        /* General Styles */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: white; /* Gradient background */
            margin: 0;
            font-family: 'Poppins', sans-serif; /* Modern font */
        }

        .container {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Animation Section */
        .animation-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        #lottie-animation {
            width: 100%;
            max-width: 800px; /* Increased animation size for large screens */
            height: auto;
        }

        /* Login Section */
        .login-section {
            flex: 1;
            max-width: 400px;
            padding: 2rem;
            text-align: center;
        }

        .login-section h3 {
            color: #007bff; /* Blue text for the heading */
            margin-bottom: 1.5rem;
            font-size: 2rem; /* Larger font size */
            font-weight: 600; /* Bold font weight */
            animation: fadeInDown 1s ease-in-out; /* Fade-in animation */
        }

        .form-control {
            border-radius: 10px; /* Rounded corners */
            border: 1px solid #ccc; /* Light gray border */
            padding: 0.75rem;
            margin-bottom: 1rem;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3); /* Subtle glow effect */
            transform: scale(1.02); /* Slight scale-up effect */
        }

        .btn-primary {
            background-color: #007bff; /* Blue button */
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Lift effect on hover */
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3); /* Shadow on hover */
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        footer {
            background-color: rgba(248, 249, 250, 0.8); /* Semi-transparent footer */
            padding: 1rem 0;
            text-align: center;
        }

        /* Animations */
        @keyframes fadeInDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control, .btn-primary, .forgot-password {
            animation: fadeInUp 1s ease-in-out; /* Fade-in animation for form elements */
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .container {
                flex-direction: column; /* Stack vertically on smaller screens */
                padding: 1rem;
            }

            .animation-section {
                order: 1; /* Animation on top */
                margin-top: 2rem;
            }

            .login-section {
                order: 2; /* Login below animation */
                max-width: 100%;
                padding: 1rem;
            }

            #lottie-animation {
                max-width: 300px; /* Smaller animation for mobile */
            }

            .login-section h3 {
                font-size: 1.75rem; /* Smaller heading for mobile */
            }
        }

        @media (max-width: 480px) {
            #lottie-animation {
                max-width: 200px; /* Even smaller animation for very small screens */
            }

            .login-section h3 {
                font-size: 1.5rem; /* Smaller heading for very small screens */
            }
        }
    </style>
    <!-- Load Poppins font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <!-- Animation Section (Left Side on Full Screen) -->
    <div class="animation-section">
        <div id="lottie-animation"></div>
    </div>

    <!-- Login Section (Right Side on Full Screen) -->
    <div class="login-section">
        <h3>Welcome Back 😊</h3>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="authenticate.php" method="POST" onsubmit="return validateForm()">
            <div class="mb-3">
                <input type="text" id="email" name="email" class="form-control" placeholder="Enter email | mobile number" required>
            </div>
            <div class="mb-3">
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>

<script>
    function validateForm() {
        let email = document.getElementById("email").value.trim();
        let password = document.getElementById("password").value.trim();
        let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        let mobilePattern = /^[6-9]\d{9}$/;

        if (!emailPattern.test(email) && !mobilePattern.test(email)) {
            alert("Please enter a valid email or mobile number.");
            return false;
        }
        if (password.length < 6) {
            alert("Password must be at least 6 characters long.");
            return false;
        }
        return true;
    }

    // Load Lottie animation
    document.addEventListener("DOMContentLoaded", function() {
        lottie.loadAnimation({
            container: document.getElementById('lottie-animation'),
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: 'lotteflies.json' // Path to your Lottie JSON file
        });
    });
</script>

</body>
</html>