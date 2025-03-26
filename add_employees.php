<?php
session_start();
include 'log_api.php';

// Set session timeout to 5 minutes (300 seconds)
$timeout = 300;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'session_timeout',
        "Session timed out due to inactivity on add employee page",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        401,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    logUserAction(
        null,
        'unknown',
        'unauthorized_access',
        "Attempted to access add employee page without login",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("Location: index.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'unauthorized_admin_access',
        "Non-admin user attempted to access add employee page",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    header("Location: summary.php");
    exit();
}

// Log successful page access
logUserAction(
    $_SESSION['emp_id'],
    $_SESSION['user'],
    'page_access',
    "Accessed add employee page",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
                <a href="summary.php" class="<?php echo ($current_page == 'summary.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="update_tc3.php" class="<?php echo ($current_page == 'update_tc3.php') ? 'active' : ''; ?>">
                    <i class="fas fa-vial"></i> Testing
                </a>
                <a href="bug_details.php" class="<?php echo ($current_page == 'bug_details.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bug"></i> Bug Reports
                </a>
                <a href="logout.php" class="text-danger <?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

                <?php if ($_SESSION['is_admin']): ?>
                    <div class="admin-section">
                        <h4 onclick="toggleAdminLinks()"><i class="fas fa-cogs"></i> Admin <i class="fas fa-chevron-down"></i></h4>
                        <div class="admin-links">
                            <a href="employees.php" class="<?php echo ($current_page == 'employees.php') ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i> Employees
                            </a>
                            <a href="apk_up.php" class="<?php echo ($current_page == 'apk_up.php') ? 'active' : ''; ?>">
                                <i class="fas fa-upload"></i> APK Admin
                            </a>
                            <a href="index1.php" class="<?php echo ($current_page == 'index1.php') ? 'active' : ''; ?>">
                                <i class="fas fa-list-alt"></i> TCM
                            </a>
                            <a href="view_logs.php">
                                <i class="fas fa-clipboard-list"></i> View Logs
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Expiring Soon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Your session will expire in 2 minutes. Please save your work.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Session timeout in milliseconds (5 minutes)
        const sessionTimeout = 5 * 60 * 1000;
        const popupTime = 2 * 60 * 1000;

        // Show the session timeout popup
        setTimeout(() => {
            const sessionPopup = new bootstrap.Modal(document.getElementById('sessionPopup'));
            sessionPopup.show();
            
            // Log session timeout warning
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'session_timeout_warning',
                    description: 'Session timeout warning shown on add employee page'
                },
                dataType: 'json'
            });
        }, sessionTimeout - popupTime);

        // Logout the user after session timeout
        setTimeout(() => {
            window.location.href = 'logout.php';
        }, sessionTimeout);

        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
            
            // Log admin links toggle
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'admin_links_toggle',
                    description: 'Toggled admin links visibility'
                },
                dataType: 'json'
            });
        }

        $(document).ready(function() {
            // Log client-side page load
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'page_load',
                    description: 'Loaded add employee page'
                },
                dataType: 'json'
            });

            // Fetch auto-generated emp_id when the page loads
            $.ajax({
                type: 'GET',
                url: 'generate_emp_id.php',
                success: function(response) {
                    if (response.status === 'success') {
                        $('input[name="emp_id"]').val(response.emp_id);
                        
                        // Log successful emp_id generation
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'emp_id_generated',
                                description: 'Generated employee ID: ' + response.emp_id,
                                emp_id: response.emp_id
                            },
                            dataType: 'json'
                        });
                    } else {
                        $('#status-message').removeClass('alert-success').addClass('alert-danger');
                        $('#status-message').text(response.message);
                        $('#status-message').show();
                        
                        // Log emp_id generation failure
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'emp_id_generation_failed',
                                description: response.message,
                                error: response.message
                            },
                            dataType: 'json'
                        });
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
                    
                    // Log AJAX error for emp_id generation
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'emp_id_generation_error',
                            description: errorMsg,
                            error: errorMsg
                        },
                        dataType: 'json'
                    });
                }
            });

            // Handle form submission via AJAX
            $('#addEmployeeForm').on('submit', function(e) {
                e.preventDefault();

                // Log form submission attempt
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'employee_add_attempt',
                        description: 'Attempting to add new employee',
                        form_data: $('#addEmployeeForm').serialize()
                    },
                    dataType: 'json'
                });

                // Serialize form data
                const formData = {
                    emp_id: $('input[name="emp_id"]').val(),
                    emp_name: $('input[name="emp_name"]').val(),
                    email: $('input[name="email"]').val(),
                    mobile_number: $('input[name="mobile_number"]').val(),
                    designation: $('input[name="designation"]').val(),
                    password: $('input[name="password"]').val(),
                    is_admin: $('input[name="is_admin"]').is(':checked') ? 1 : 0
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

                            // Log successful employee addition
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'employee_add_success',
                                    description: 'Added new employee: ' + formData.emp_name + ' (ID: ' + formData.emp_id + ')',
                                    emp_id: formData.emp_id,
                                    emp_name: formData.emp_name,
                                    is_admin: formData.is_admin
                                },
                                dataType: 'json'
                            });

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
                            
                            // Log employee addition failure
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'employee_add_failed',
                                    description: response.message,
                                    error: response.message,
                                    form_data: formData
                                },
                                dataType: 'json'
                            });
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
                        
                        // Log AJAX error for employee addition
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'employee_add_error',
                                description: errorMsg,
                                error: errorMsg,
                                form_data: formData
                            },
                            dataType: 'json'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>