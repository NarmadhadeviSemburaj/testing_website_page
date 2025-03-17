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

// Ensure only admins can access
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
    <title>APK Admin</title>
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
            right: 50px;
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
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

    <script>
		// Session timeout in milliseconds (5 minutes)
        const sessionTimeout = 5 * 60 * 1000; // 5 minutes in milliseconds

        // Time before showing the popup (2 minutes before timeout)
        const popupTime = 2 * 60 * 1000; // 2 minutes in milliseconds

        // Show the session timeout popup
        setTimeout(() => {
            const sessionPopup = new bootstrap.Modal(document.getElementById('sessionPopup'));
            sessionPopup.show();
        }, sessionTimeout - popupTime);

        // Logout the user after session timeout
        setTimeout(() => {
            window.location.href = 'logout.php';
        }, sessionTimeout);
        // Function to toggle the visibility of the Create Folder form
        function toggleCreateFolderForm() {
            const form = document.getElementById('createFolderForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Function to toggle admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            if (adminLinks.style.display === 'block') {
                adminLinks.style.display = 'none';
            } else {
                adminLinks.style.display = 'block';
            }
        }

        // Handle Create Folder Form Submission
        document.getElementById('createFolderFormData').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_folder');

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

                    // Handle Overwrite Form Submission
                    document.getElementById('overwriteFolderForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        const overwriteFormData = new FormData(this);
                        overwriteFormData.append('action', 'create_folder');

                        fetch('apk_api_up.php', {
                            method: 'POST',
                            body: overwriteFormData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                                setTimeout(() => location.reload(), 1000); // Reload page after 1 second
                            } else {
                                document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    });
                } else if (data.status === 'success') {
                    document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => location.reload(), 1000); // Reload page after 1 second
                } else {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Handle Upload APK Form Submission
        document.getElementById('uploadApkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'upload_apk');

            fetch('apk_api_up.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('message').innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => location.reload(), 1000); // Reload page after 1 second
                } else {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>