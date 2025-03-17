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

// Ensure only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch folders dynamically
$folders = array_filter(glob('uploads/*'), 'is_dir');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK Download - Test Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add your CSS styles here */
        html, body { height: 100%; margin: 0; padding: 0; background-color: #f0f0f0; overflow: hidden; }
        .wrapper { display: flex; height: 100vh; padding: 20px; }
        .sidebar-container { width: 200px; height: 100vh; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1); margin-right: 20px; overflow: hidden; position: fixed; left: 20px; top: 20px; bottom: 20px; }
        .sidebar a { display: block; padding: 10px; margin: 10px 0; text-decoration: none; color: #333; border-radius: 10px; transition: background-color 0.3s; }
        .sidebar a:hover, .sidebar a.active { background-color: #007bff; color: #fff; }
        .sidebar a i { margin-right: 10px; }
        .content-container { flex: 1; background-color: #fff; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); padding: 20px; height: 100vh; margin-left: 220px; overflow-y: auto; }
        .admin-section h4 { font-size: 16px; cursor: pointer; }
        .admin-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        .user-info { text-align: center; margin-bottom: 20px; }
        .user-info i { font-size: 20px; margin-right: 5px; }
        .user-info h4 { font-size: 16px; margin: 5px 0 0; color: #333; }
        .admin-links { display: none; }
        .card { border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); top: 30px; padding: 20px; height: 200px; width: 400px; }
        .btn-primary { background-color: #007bff !important; border: none; }
        .btn-primary:hover { background-color: #0056b3 !important; }
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
                                <i class="fas fa-list-alt"></i> Test Case Manager
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <h4>APK Download</h4>
            <div class="card">
                <form>
                    <div class="mb-3">
                        <select id="folderSelect" class="form-select">
                            <option value="">Select Folder</option>
                            <?php foreach ($folders as $folder): ?>
                                <option value="<?= basename($folder) ?>"><?= basename($folder) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select id="versionSelect" class="form-select" disabled>
                            <option value="">Select Version</option>
                        </select>
                    </div>
                    <button type="button" id="downloadBtn" class="btn btn-primary w-100" disabled>Download</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        let versionMap = {}; // Stores file names mapped to version names

        document.getElementById("folderSelect").addEventListener("change", function() {
            let folder = this.value;
            let versionSelect = document.getElementById("versionSelect");
            let downloadBtn = document.getElementById("downloadBtn");

            versionSelect.innerHTML = "<option value=''>Loading...</option>";
            versionSelect.disabled = true;
            downloadBtn.disabled = true;
            versionMap = {}; // Reset version mapping

            if (folder) {
                fetch(`apk_download_api.php?fetch_versions=${folder}`)
                .then(response => response.json())
                .then(data => {
                    versionSelect.innerHTML = "<option value=''>Select Version</option>";

                    data.forEach(item => {
                        versionMap[item.version] = item.filename; // Map version to filename
                        versionSelect.innerHTML += `<option value='${item.version}'>${item.version}</option>`;
                    });

                    versionSelect.disabled = false;
                });
            }
        });

        document.getElementById("versionSelect").addEventListener("change", function() {
            document.getElementById("downloadBtn").disabled = !this.value;
        });

        document.getElementById("downloadBtn").addEventListener("click", function() {
            let folder = document.getElementById("folderSelect").value;
            let version = document.getElementById("versionSelect").value;
            let filename = versionMap[version]; // Get full filename based on version

            if (folder && filename) {
                window.location.href = `uploads/${folder}/${filename}`;
            }
        });

        // Function to toggle the visibility of admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>