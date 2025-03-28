<?php
session_start();
include 'log_api.php'; // Include logging library

// Set session timeout to 5 minutes (300 seconds)
$timeout = 300;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Log session timeout
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'session_timeout',
        "Session timed out on APK Admin page",
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

// Ensure only admins can access
if (!isset($_SESSION['user'])) {
    // Log unauthorized access attempt
    logUserAction(
        null,
        'unknown',
        'unauthorized_access',
        "Attempted to access APK Admin without login",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    header("Location: home.php");
    exit();
}

if ($_SESSION['is_admin'] != 1) {
    // Log non-admin access attempt
    logUserAction(
        $_SESSION['emp_id'],
        $_SESSION['user'],
        'unauthorized_admin_access',
        "Non-admin attempted to access APK Admin",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    header("Location: home.php");
    exit();
}

// Log successful page access
logUserAction(
    $_SESSION['emp_id'],
    $_SESSION['user'],
    'page_access',
    "Accessed APK Admin page",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Define the current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Updated CSS with responsive sidebar */
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

        .btn-green {
            background-color: green !important;
            border-color: green !important;
            color: white !important;
        }

        .btn-green:hover {
            background-color: darkgreen !important;
            border-color: darkgreen !important;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        #createFolderForm {
            margin-top: 10px;
            width: 220px;
        }

        .form-control, .form-select, .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            width: 220px;
            height: 30px;
        }

        .form-control, .form-select {
            margin-bottom: 0.5rem;
        }

        .btn {
            margin-bottom: 0.5rem;
        }

        .upload-section {
            margin-top: 20px;
        }

        .createfolder {
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 1000;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        #message {
            margin-top: 20px;
        }
        
        /* Progress bar styles */
        .progress-container {
            width: 100%;
            margin-top: 10px;
            display: none;
        }
        
        .progress-bar {
            height: 20px;
            background-color: #007bff;
            width: 0%;
            transition: width 0.3s;
            border-radius: 4px;
            text-align: center;
            color: white;
            line-height: 20px;
            font-size: 12px;
        }
        
        .progress-text {
            margin-top: 5px;
            font-size: 12px;
            text-align: center;
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
            .createfolder {
                right: 50px;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1199.98px) {
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
            .createfolder {
                right: 50px;
            }
        }
        
        @media (min-width: 1200px) {
            .sidebar-toggle {
                display: none;
            }
            .createfolder {
                right: 50px;
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
        <div class="content-container" id="contentContainer">
            <!-- Header Section with Create Folder Button -->
            <div class="header-section">
                <h4 class="text-dark mb-0">APK Admin</h4>
                <button class="btn btn-primary createfolder" onclick="toggleCreateFolderForm()">
                    <i class="fas fa-folder-plus"></i>
                </button>
            </div>

            <!-- Create Folder Form (Initially Hidden) -->
            <div id="createFolderForm" class="mb-4" style="display: none;">
                <form id="createFolderFormData">
                    <div class="mb-3">
                        <input type="text" name="folder_name" class="form-control" placeholder="Enter Folder name" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Folder</button>
                </form>
            </div>

            <!-- Upload APK Section -->
            <form id="uploadApkForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <select name="folder_select" class="form-select" required>
                        <option value="">Select Folder</option>
                        <?php
                        $folders = array_filter(glob('uploads/*'), 'is_dir');
                        foreach ($folders as $folder) {
                            $folder_name = basename($folder);
                            echo "<option value='$folder_name'>$folder_name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <input type="file" name="apk_file" class="form-control" accept=".apk" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-green">Upload APK</button>
                
                <!-- Progress bar container -->
                <div class="progress-container" id="progressContainer">
                    <div class="progress-bar" id="progressBar">0%</div>
                    <div class="progress-text" id="progressText">Uploading...</div>
                </div>
            </form>

            <!-- Display Messages -->
            <div id="message"></div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    description: 'Session timeout warning shown on APK Admin'
                },
                dataType: 'json'
            });
        }, sessionTimeout - popupTime);

        // Logout the user after session timeout
        setTimeout(() => {
            window.location.href = 'logout.php';
        }, sessionTimeout);

        // Function to toggle the visibility of the Create Folder form
        function toggleCreateFolderForm() {
            const form = document.getElementById('createFolderForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            // Log folder form toggle
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'folder_form_toggle',
                    description: 'Toggled create folder form visibility'
                },
                dataType: 'json'
            });
        }

        // Function to toggle admin links
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

        // Sidebar toggle functionality
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarToggle').click(function() {
                $('#sidebarContainer').toggleClass('show');
                $('#contentContainer').toggleClass('expanded');
            });

            // Close sidebar when clicking outside on mobile/tablet
            $(document).click(function(e) {
                if ($(window).width() < 1200) {
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
                if ($(window).width() >= 1200) {
                    $('#sidebarContainer').removeClass('show');
                    $('#contentContainer').removeClass('expanded');
                }
            });

            // Handle Create Folder Form Submission
            document.getElementById('createFolderFormData').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'create_folder');
                
                // Log folder creation attempt
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'folder_creation_attempt',
                        description: 'Attempting to create folder: ' + formData.get('folder_name')
                    },
                    dataType: 'json'
                });

                fetch('apk_api_up.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'error' && data.message === 'Folder already exists. Overwrite?') {
                        const overwriteForm = `
                            <div class="alert alert-danger">${data.message}</div>
                            <form id="overwriteFolderForm" class="mt-3">
                                <input type="hidden" name="folder_name" value="${formData.get('folder_name')}">
                                <input type="hidden" name="overwrite" value="yes">
                                <button type="submit" class="btn btn-warning">Overwrite</button>
                            </form>
                        `;
                        document.getElementById('message').innerHTML = overwriteForm;

                        // Log folder exists warning
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'folder_exists_warning',
                                description: 'Folder already exists: ' + formData.get('folder_name')
                            },
                            dataType: 'json'
                        });

                        // Handle Overwrite Form Submission
                        document.getElementById('overwriteFolderForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const overwriteFormData = new FormData(this);
                            overwriteFormData.append('action', 'create_folder');
                            
                            // Log folder overwrite attempt
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'folder_overwrite_attempt',
                                    description: 'Attempting to overwrite folder: ' + overwriteFormData.get('folder_name')
                                },
                                dataType: 'json'
                            });

                            fetch('apk_api_up.php', {
                                method: 'POST',
                                body: overwriteFormData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                                    
                                    // Log successful folder overwrite
                                    $.ajax({
                                        url: 'log_api.php',
                                        type: 'POST',
                                        data: {
                                            action: 'log_client_action',
                                            action_type: 'folder_overwrite_success',
                                            description: 'Successfully overwrote folder: ' + overwriteFormData.get('folder_name')
                                        },
                                        dataType: 'json'
                                    });
                                    
                                    setTimeout(() => location.reload(), 1000);
                                } else {
                                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                                    
                                    // Log folder overwrite failure
                                    $.ajax({
                                        url: 'log_api.php',
                                        type: 'POST',
                                        data: {
                                            action: 'log_client_action',
                                            action_type: 'folder_overwrite_failed',
                                            description: 'Failed to overwrite folder: ' + overwriteFormData.get('folder_name') + ' - ' + data.message
                                        },
                                        dataType: 'json'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                // Log AJAX error
                                $.ajax({
                                    url: 'log_api.php',
                                    type: 'POST',
                                    data: {
                                        action: 'log_client_action',
                                        action_type: 'folder_overwrite_error',
                                        description: 'Error during folder overwrite: ' + error.message
                                    },
                                    dataType: 'json'
                                });
                            });
                        });
                    } else if (data.status === 'success') {
                        document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        
                        // Log successful folder creation
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'folder_creation_success',
                                description: 'Successfully created folder: ' + formData.get('folder_name')
                            },
                            dataType: 'json'
                        });
                        
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        
                        // Log folder creation failure
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'folder_creation_failed',
                                description: 'Failed to create folder: ' + formData.get('folder_name') + ' - ' + data.message
                            },
                            dataType: 'json'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Log AJAX error
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'folder_creation_error',
                            description: 'Error during folder creation: ' + error.message
                        },
                        dataType: 'json'
                    });
                });
            });

            // Handle Upload APK Form Submission with progress tracking
            document.getElementById('uploadApkForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                formData.append('action', 'upload_apk');
                
                // Show progress bar
                document.getElementById('progressContainer').style.display = 'block';
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                
                // Log APK upload attempt
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'apk_upload_attempt',
                        description: 'Attempting to upload APK to folder: ' + formData.get('folder_select')
                    },
                    dataType: 'json'
                });

                const xhr = new XMLHttpRequest();
                
                // Progress event handler
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        progressBar.textContent = percentComplete + '%';
                        progressText.textContent = `Uploading: ${percentComplete}%`;
                        
                        // Log upload progress periodically
                        if (percentComplete % 25 === 0) {
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'apk_upload_progress',
                                    description: `APK upload progress: ${percentComplete}%`
                                },
                                dataType: 'json'
                            });
                        }
                    }
                });
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.status === 'success') {
                                document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                                
                                // Log successful APK upload
                                $.ajax({
                                    url: 'log_api.php',
                                    type: 'POST',
                                    data: {
                                        action: 'log_client_action',
                                        action_type: 'apk_upload_success',
                                        description: 'Successfully uploaded APK to folder: ' + formData.get('folder_select')
                                    },
                                    dataType: 'json'
                                });
                                
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                                
                                // Log APK upload failure
                                $.ajax({
                                    url: 'log_api.php',
                                    type: 'POST',
                                    data: {
                                        action: 'log_client_action',
                                        action_type: 'apk_upload_failed',
                                        description: 'Failed to upload APK: ' + data.message
                                    },
                                    dataType: 'json'
                                });
                            }
                        } catch (error) {
                            document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error processing response</div>`;
                            
                            // Log response parsing error
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'apk_upload_error',
                                    description: 'Error parsing upload response: ' + error.message
                                },
                                dataType: 'json'
                            });
                        }
                        // Hide progress bar
                        document.getElementById('progressContainer').style.display = 'none';
                    }
                };
                
                xhr.open('POST', 'apk_api_up.php', true);
                xhr.send(formData);
            });
            
            // Log page load
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: 'page_load',
                    description: 'Loaded APK Admin page'
                },
                dataType: 'json'
            });
        });
    </script>
</body>
</html>