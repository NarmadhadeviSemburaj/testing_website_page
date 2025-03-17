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
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleared Bugs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        
		html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0; /* Light Grey Background */
            overflow: hidden; /* Prevents unwanted scrolling */
        }

        /* Wrapper to hold both sidebar and content */
        .wrapper {
            display: flex;
            height: 100vh; /* Full viewport height */
            padding: 20px;
        }

        /* Sidebar: Fixed, No Scrolling */
        .sidebar-container {
            width: 200px;
            height: 100vh; /* Fixed height */
            background-color: #fff; /* Sidebar color */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
            overflow: hidden; /* Prevents sidebar scrolling */
            position: fixed; /* Keeps sidebar fixed */
            left: 20px; /* Keeps margin spacing */
            top: 20px; /* Keeps margin spacing */
            bottom: 20px;
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
            margin-right: 10px; /* Adjust spacing */
        }

        /* Content Container: Scrollable */
        .content-container {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            height: 100vh; /* Fixed height */
            margin-left: 220px; /* Offset for the fixed sidebar */
            overflow-y: auto; /* Enables scrolling */
        }

        .admin-section h4 {
            font-size: 16px; /* Match this to the sidebar links' font size */
            cursor: pointer; /* Indicates it's clickable */
        }
        .admin-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
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
            display: none; /* Initially hidden */
        }

        /* Card styling */
        .bug-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: #fff;
            padding: 15px;
        }

        .bug-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .bug-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .bug-card-header h5 {
            margin: 0;
            font-size: 16px;
        }

        .bug-type {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .bug-type-critical {
            background-color: #dc3545; /* Red */
        }

        .bug-type-high {
            background-color: #fd7e14; /* Orange */
        }

        .bug-type-low {
            background-color: #ffc107; /* Yellow */
            color: #212529;
        }

        .bug-card-body {
            margin-bottom: 10px;
        }

        .bug-info {
            margin-bottom: 10px;
        }

        .bug-info label {
            font-weight: bold;
            margin-bottom: 3px;
            display: block;
            color: #555;
        }

        .bug-info p {
            margin: 0;
            overflow-wrap: break-word;
        }

        .expandable-section {
            display: none; /* Hidden by default */
        }

        .expandable-section.expanded {
            display: block; /* Show when expanded */
        }

        .view-more-btn {
            color: #007bff;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
        }

        .view-more-btn:hover {
            text-decoration: underline;
        }

        .attachment-preview {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 10px;
            max-height: 150px;
        }

        .filter-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 20px;
        }

        .filter-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .filter-row label {
            font-size: 14px;
        }

        .clear-bugs-btn {
            margin-bottom: 20px;
            text-align: right;
        }

        /* Blue icons */
        .bug-info i {
            color: #007bff;
        }

        /* View attachment button */
        .view-attachment-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #007bff;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .view-attachment-btn:hover {
            background-color: #e9ecef;
        }

        /* For two columns inside card */
        .bug-card-columns {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .bug-card-column {
            flex: 1;
            min-width: 250px;
            padding: 0 10px;
        }

        /* Move "Mark as Cleared" button to the right */
        .bug-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
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
                                <i class="fas fa-list-alt"></i> Test Case Manager
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Cleared Bugs</h4>
                <a href="bug_details.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Bug Reports
                </a>
            </div>

            <!-- Cleared Bugs Cards Container -->
            <div class="row bug-cards-container" id="clearedBugsContainer">
                <!-- Cleared bugs will be dynamically loaded here -->
            </div>

            <!-- Empty state message when no cleared bugs are found -->
            <div id="emptyState" class="text-center py-5" style="display:none;">
                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                <h4 class="mt-3">No Cleared Bugs Found</h4>
                <p class="text-muted">No bugs have been marked as cleared yet.</p>
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
            // Toggle admin links visibility
            function toggleAdminLinks() {
                const adminLinks = document.querySelector('.admin-links');
                adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
            }

            // Fetch cleared bugs from the API
            function fetchClearedBugs() {
                fetch('cleared_bugs_api.php')
                    .then(response => response.json())
                    .then(data => {
                        const clearedBugsContainer = document.getElementById('clearedBugsContainer');
                        const emptyState = document.getElementById('emptyState');

                        if (data.status === 'success' && data.data.length > 0) {
                            clearedBugsContainer.innerHTML = ''; // Clear existing content

                            data.data.forEach(bug => {
                                const bugCard = `
                                    <div class="col-md-6 col-lg-4">
                                        <div class="bug-card">
                                            <div class="bug-card-header">
                                                <h5>${bug.Module_name}</h5>
                                                <span class="badge bg-success">Cleared</span>
                                            </div>
                                            <div class="bug-card-body">
                                                <div class="bug-info">
                                                    <label><i class="fas fa-align-left"></i> Description</label>
                                                    <p>${bug.description}</p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-list-ol"></i> Test Steps</label>
                                                    <p>${bug.test_steps}</p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-check-circle"></i> Expected Result</label>
                                                    <p>${bug.expected_results}</p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-times-circle"></i> Actual Result</label>
                                                    <p>${bug.actual_result}</p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-user"></i> Cleared By</label>
                                                    <p>${bug.cleared_by}</p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="far fa-calendar-alt"></i> Cleared At</label>
                                                    <p>${bug.result_changed_at}</p>
                                                </div>

												
                                            </div>
                                        </div>
                                    </div>
                                `;
                                clearedBugsContainer.insertAdjacentHTML('beforeend', bugCard);
                            });

                            emptyState.style.display = 'none'; // Hide empty state
                        } else {
                            clearedBugsContainer.innerHTML = ''; // Clear existing content
                            emptyState.style.display = 'block'; // Show empty state
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching cleared bugs:', error);
                        alert('Failed to fetch cleared bugs. Check the console for details.');
                    });
            }

            // Fetch cleared bugs when the page loads
            document.addEventListener('DOMContentLoaded', fetchClearedBugs);
        </script>
    </body>
</html>
        