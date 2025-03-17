<?php
session_start();
// Set session timeout to 5 minutes (300 seconds)
$timeout = 300; // 5 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Last request was more than 5 minutes ago
    session_unset();     // Unset $_SESSION variable for this page
    session_destroy();   // Destroy session data
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp
// Ensure only logged-in admin users can access
if (!isset($_SESSION['user']) || $_SESSION['is_admin'] != 1) {
    header("Location: home.php");
    exit();
}

// Define the current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Test Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
		html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            overflow: hidden;
        }

        .wrapper {
            display: flex;
            height: 100vh;
            padding: 20px;
        }

        .sidebar-container {
            width: 200px;
            height: 100vh;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
            overflow: hidden;
            position: fixed;
            left: 20px;
            top: 20px;
            bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            margin: 10px 0;
            text-decoration: none;
            color: #333;
            border-radius: 10px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: #007bff;
            color: #fff;
        }
        
        .sidebar a i {
            margin-right: 10px;
        }

        .content-container {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: 100vh;
            margin-left: 220px;
            overflow-y: auto;
        }
        
        .admin-section h4 {
            font-size: 16px;
            cursor: pointer;
        }
        
        .admin-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .user-info i {
            font-size: 20px;
            margin-right: 5px;
        }

        .user-info h4 {
            font-size: 16px;
            margin: 5px 0 0;
            color: #333;
        }

        .form-container {
            max-width: 800px;
            margin-left: 30px;
        }
        
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        #status-message {
            display: none;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar-container">
            <!-- User Info Section -->
            <div class="user-info">
                <i class="fas fa-user"></i>
                <h4><?php echo htmlspecialchars($_SESSION['user']); ?></h4>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar">
                <!-- Your existing sidebar links -->
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <h4 class="text-dark mb-4">Add Employee</h4>
            <div class="alert" id="status-message" role="alert"></div>
            <div class="form-container">
                <form id="addEmployeeForm">
                    <div class="mb-3">
                        <label class="form-label">Employee ID:</label>
                        <input type="text" name="emp_id" class="form-control" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employee Name:</label>
                        <input type="text" name="emp_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number:</label>
                        <input type="text" name="mobile_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Designation:</label>
                        <input type="text" name="designation" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_admin" value="1">
                        <label class="form-check-label">Admin Access</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Session Timeout Popup -->
    <div id="sessionPopup" class="modal fade" tabindex="-1">
        <!-- Your existing modal code -->
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fetch auto-generated emp_id when the page loads
            $.ajax({
                type: 'GET',
                url: 'generate_emp_id.php',
                success: function(response) {
                    if (response.status === 'success') {
                        $('input[name="emp_id"]').val(response.emp_id);
                    } else {
                        $('#status-message').removeClass('alert-success').addClass('alert-danger');
                        $('#status-message').text(response.message);
                        $('#status-message').show();
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred while generating Employee ID';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    $('#status-message').removeClass('alert-success').addClass('alert-danger');
                    $('#status-message').text(errorMsg);
                    $('#status-message').show();
                }
            });

            // Handle form submission via AJAX
            $('#addEmployeeForm').on('submit', function(e) {
                e.preventDefault();

                // Serialize form data
                const formData = {
                    emp_id: $('input[name="emp_id"]').val(),
                    emp_name: $('input[name="emp_name"]').val(),
                    email: $('input[name="email"]').val(),
                    mobile_number: $('input[name="mobile_number"]').val(),
                    designation: $('input[name="designation"]').val(),
                    password: $('input[name="password"]').val(),
                    is_admin: $('input[name="is_admin"]').is(':checked') ? 1 : 0 // Ensure is_admin is 0 when unchecked
                };

                // Send AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'add_employee_api.php',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#status-message').removeClass('alert-danger').addClass('alert-success');
                            $('#status-message').text(response.message);
                            $('#status-message').show();

                            // Reset form
                            $('#addEmployeeForm')[0].reset();

                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = 'employees.php';
                            }, 2000);
                        } else {
                            $('#status-message').removeClass('alert-success').addClass('alert-danger');
                            $('#status-message').text(response.message);
                            $('#status-message').show();
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $('#status-message').removeClass('alert-success').addClass('alert-danger');
                        $('#status-message').text(errorMsg);
                        $('#status-message').show();
                    }
                });
            });
        });
    </script>
</body>
</html>