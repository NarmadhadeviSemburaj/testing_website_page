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
        "Session timed out due to inactivity",
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
        "Attempted to access employees page without login",
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
        "Non-admin user attempted to access employees page",
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
    "Accessed employees management page",
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
    <title>Manage Employees - Test Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            overflow-x: hidden;
        }

        /* Wrapper to hold both sidebar and content */
        .wrapper {
            display: flex;
            min-height: 100vh;
            padding: 20px;
        }

        /* Sidebar styles */
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
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-container.collapsed {
            transform: translateX(-240px);
        }

        .sidebar-container.show {
            transform: translateX(0);
        }

        /* Content container */
        .content-container {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            min-height: 100vh;
            margin-left: 220px;
            transition: margin-left 0.3s ease;
        }

        .content-container.expanded {
            margin-left: 20px;
        }

        /* Sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            left: 3px;
            top: 20px;
            z-index: 1050;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            width: 35px;
            height: 35px;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
        }

        /* Sidebar Links */
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

        /* Admin section */
        .admin-section h4 {
            font-size: 16px;
            cursor: pointer;
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            transition: background-color 0.3s;
        }

        .admin-section h4:hover {
            background-color: #007bff;
            color: #fff;
        }

        .admin-section {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        /* User Info */
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

        .admin-links {
            display: none;
        }

        /* Table styles */
        .table th {
            background-color: #007bff;
            color: white;
            text-align: center;
        }

        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }

        /* Employee cards for mobile/tablet */
        .employee-cards {
            display: none;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }

        .employee-card {
            width: 100%;
            max-width: 350px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-field {
            margin-bottom: 10px;
        }

        .card-field strong {
            display: inline-block;
            width: 100px;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        /* Add employee button */
        .add-employee-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #007bff;
            color: white;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .add-employee-btn:hover {
            background-color: #0056b3;
        }

        /* Responsive styles */
        @media (max-width: 767.98px) {
            .sidebar-container {
                transform: translateX(-240px);
            }
            .sidebar-container.show {
                transform: translateX(0);
            }
            .content-container {
                margin-left: 20px;
            }
            .sidebar-toggle {
                display: flex;
            }
            .add-employee-btn {
            right: 40px;
            }
            /* Hide table on mobile, show cards */
            .table-responsive {
                display: none;
            }
            .employee-cards {
                display: flex;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1199px) {
            .sidebar-container {
                transform: translateX(-240px);
            }
            .sidebar-container.show {
                transform: translateX(0);
            }
            .content-container {
                margin-left: 20px;
            }
            .sidebar-toggle {
                display: flex;
            }
            
            /* Show both table and cards on tablet */
            .table-responsive {
                display: none;
            }
            .employee-cards {
                display: flex;
            }
            .add-employee-btn {
            right: 40px;
            }
        }
        
        @media (min-width: 1200px) {
            .sidebar-toggle {
                display: none;
            }
            
            /* Show only table on desktop */
            .table-responsive {
                display: block;
            }
            .employee-cards {
                display: none;
            }
            .add-employee-btn {
            right: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar-container" id="sidebarContainer">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <h4><?php echo htmlspecialchars($_SESSION['user']); ?></h4>
            </div>

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

                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
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
        <div class="content-container" id="contentContainer">
            <h4 class="mb-4">Employees</h4>
            
            <!-- Table for desktop view -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="employeesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Designation</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Employee rows will be populated here by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Cards for mobile view -->
            <div class="employee-cards" id="employeeCards">
                <!-- Employee cards will be populated here by JavaScript -->
            </div>
        </div>
    </div>
    
    <a href="add_employees.php" class="btn add-employee-btn">+</a>
    
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                    description: 'Session timeout warning shown on Employees page'
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

        function fetchEmployees() {
            console.log("Starting to fetch employees...");
            
            $.ajax({
                url: 'employee.php?action=getEmployees',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log("API Response:", response);
                    
                    if (response && response.status === 'success' && Array.isArray(response.data)) {
                        const tbody = $('#employeesTable tbody');
                        const cardsContainer = $('#employeeCards');
                        tbody.empty();
                        cardsContainer.empty();
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="7" class="text-center">No employees found</td></tr>');
                            cardsContainer.append('<div class="text-center w-100">No employees found</div>');
                            return;
                        }
                        
                        response.data.forEach(employee => {
                            // Table row
                            const row = `
                                <tr>
                                    <td>${employee.emp_id}</td>
                                    <td>${employee.emp_name}</td>
                                    <td>${employee.email}</td>
                                    <td>${employee.mobile_number}</td>
                                    <td>${employee.designation}</td>
                                    <td>${employee.is_admin == 1 ? 'Yes' : 'No'}</td>
                                    <td>
                                        <a href="edit_employees.php?id=${employee.emp_id}" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="delete_employees.php?id=${employee.emp_id}" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this employee?');">
                                           Delete
                                        </a>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                            
                            // Card for mobile view
                            const card = `
                                <div class="employee-card">
                                    <div class="card-field"><strong>ID:</strong> ${employee.emp_id}</div>
                                    <div class="card-field"><strong>Name:</strong> ${employee.emp_name}</div>
                                    <div class="card-field"><strong>Email:</strong> ${employee.email}</div>
                                    <div class="card-field"><strong>Mobile:</strong> ${employee.mobile_number}</div>
                                    <div class="card-field"><strong>Designation:</strong> ${employee.designation}</div>
                                    <div class="card-field"><strong>Admin:</strong> ${employee.is_admin == 1 ? 'Yes' : 'No'}</div>
                                    <div class="card-actions">
                                        <a href="edit_employees.php?id=${employee.emp_id}" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="delete_employees.php?id=${employee.emp_id}" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this employee?');">
                                           Delete
                                        </a>
                                    </div>
                                </div>
                            `;
                            cardsContainer.append(card);
                        });
                    } else {
                        console.error("Invalid response format or empty data");
                        $('#employeesTable tbody').html(`
                            <tr>
                                <td colspan="7" class="text-center text-danger">
                                    ${response.message || 'Invalid data format received from server'}
                                </td>
                            </tr>
                        `);
                        $('#employeeCards').html(`
                            <div class="text-center text-danger w-100">
                                ${response.message || 'Invalid data format received from server'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.log("Full error response:", xhr.responseText);
                    
                    $('#employeesTable tbody').html(`
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                Failed to load employees. Check console for details.
                            </td>
                        </tr>
                    `);
                    $('#employeeCards').html(`
                        <div class="text-center text-danger w-100">
                            Failed to load employees. Check console for details.
                        </div>
                    `);
                }
            });
        }

        function deleteEmployee(empId, empName) {
            if (confirm(`Are you sure you want to delete ${empName}?`)) {
                $.ajax({
                    url: `employee.php?action=deleteEmployee&id=${empId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Employee deleted successfully');
                            fetchEmployees(); // Refresh the list
                        } else {
                            alert('Error: ' + (response.message || 'Failed to delete employee'));
                        }
                    },
                    error: function() {
                        alert('Failed to delete employee. Please try again.');
                    }
                });
            }
        }

        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarToggle').click(function() {
                $('#sidebarContainer').toggleClass('show');
                $('#contentContainer').toggleClass('expanded');
            });

            // Close sidebar when clicking outside on mobile/tablet
            $(document).click(function(e) {
                if ($(window).width() < 992) {
                    if (!$(e.target).closest('#sidebarContainer').length && 
                        !$(e.target).is('#sidebarToggle') && 
                        $('#sidebarContainer').hasClass('show')) {
                        $('#sidebarContainer').removeClass('show');
                        $('#contentContainer').addClass('expanded');
                    }
                }
            });

            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() >= 992) {
                    $('#sidebarContainer').removeClass('show');
                    $('#contentContainer').removeClass('expanded');
                }
            });

            // Initialize with hidden admin links
            if (document.querySelector('.admin-section')) {
                document.querySelector('.admin-links').style.display = 'none';
            }
            
            // Load employees initially
            fetchEmployees();
            
            // Log page load
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'page_load',
                    description: 'Loaded Employees page'
                },
                dataType: 'json'
            });
        });
    </script>
</body>
</html>