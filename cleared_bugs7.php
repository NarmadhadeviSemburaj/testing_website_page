<?php
session_start();
// Set session timeout to 5 minutes (300 seconds)
$timeout = 300; // 5 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Last request was more than 5 minutes ago
    session_unset();     // Unset $_SESSION variable for this page
    session_destroy();   // Destroy session data
    header("Location: index.php");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
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

        /* Updated Card Styling */
        .bug-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: #fff;
            padding: 20px;
        }

        .bug-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .bug-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .bug-card-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .badge {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .bug-card-body {
            margin-bottom: 10px;
        }

        /* Two-column layout for compact fields */
        .bug-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .bug-info {
            margin-bottom: 0;
        }

        .bug-info label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
            color: #555;
            font-size: 13px;
        }

        .bug-info p {
            margin: 0;
            overflow-wrap: break-word;
            color: #333;
            font-size: 14px;
        }

        .bug-info i {
            color: #007bff;
            margin-right: 5px;
            width: 16px;
            text-align: center;
        }

        /* Expandable section styling */
        .expandable-section {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
        }

        .expandable-section.expanded {
            display: block;
        }

        .view-more-btn {
            color: #007bff;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .view-more-btn:hover {
            text-decoration: underline;
        }

        .view-more-btn i {
            margin-left: 5px;
            transition: transform 0.2s;
        }

        .view-more-btn.expanded i {
            transform: rotate(180deg);
        }

        /* Attachment styling */
        .attachment-preview {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 10px;
            max-height: 150px;
            border: 1px solid #e0e0e0;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }

        .empty-state h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .bug-info-grid {
                grid-template-columns: 1fr;
            }
            
            .content-container {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .sidebar-container {
                width: 100%;
                height: auto;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                margin-right: 0;
                border-radius: 0;
            }
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
            <div id="emptyState" class="empty-state" style="display:none;">
                <i class="fas fa-check-circle"></i>
                <h4>No Cleared Bugs Found</h4>
                <p>No bugs have been marked as cleared yet.</p>
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
            const sessionTimeout = 5 * 60 * 1000;
            const popupTime = 2 * 60 * 1000;

            setTimeout(() => {
                const sessionPopup = new bootstrap.Modal(document.getElementById('sessionPopup'));
                sessionPopup.show();
            }, sessionTimeout - popupTime);

            setTimeout(() => {
                window.location.href = 'logout.php';
            }, sessionTimeout);

            function toggleAdminLinks() {
                const adminLinks = document.querySelector('.admin-links');
                adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
            }

            // Function to handle view more/less toggle
            function toggleBugDetails(btn) {
                const card = btn.closest('.bug-card');
                const expandableSection = card.querySelector('.expandable-section');
                const icon = btn.querySelector('i');
                
                expandableSection.classList.toggle('expanded');
                btn.classList.toggle('expanded');
                
                // Update button text
                if (expandableSection.classList.contains('expanded')) {
                    btn.innerHTML = '<i class="fas fa-chevron-up"></i> View Less';
                } else {
                    btn.innerHTML = '<i class="fas fa-chevron-down"></i> View More';
                }
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
                                // Format the cleared_at date
                                const clearedDate = new Date(bug.cleared_at);
                                const formattedDate = clearedDate.toLocaleString();
                                
                                const bugCard = `
                                    <div class="col-md-6 col-lg-4">
                                        <div class="bug-card">
                                            <div class="bug-card-header">
                                                <h5>${bug.Module_name}</h5>
                                                <span class="badge bg-success">Cleared</span>
                                            </div>
                                            <div class="bug-card-body">
                                                <div class="bug-info-grid">
                                                    <div class="bug-info">
                                                        <label><i class="fas fa-tag"></i> Product</label>
                                                        <p>${bug.Product_name}</p>
                                                    </div>
                                                    <div class="bug-info">
                                                        <label><i class="fas fa-code-branch"></i> Version</label>
                                                        <p>${bug.Version}</p>
                                                    </div>
                                                    <div class="bug-info">
                                                        <label><i class="fas fa-mobile-alt"></i> Device</label>
                                                        <p>${bug.device_name}</p>
                                                    </div>
                                                    <div class="bug-info">
                                                        <label><i class="fab fa-android"></i> Android Version</label>
                                                        <p>${bug.android_version}</p>
                                                    </div>
                                                    <div class="bug-info">
                                                        <label><i class="fas fa-user"></i> Cleared By</label>
                                                        <p>${bug.cleared_by}</p>
                                                    </div>
                                                    <div class="bug-info">
                                                        <label><i class="far fa-calendar-alt"></i> Cleared At</label>
                                                        <p>${formattedDate}</p>
                                                    </div>
                                                </div>
                                                
                                                <div class="expandable-section">
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
                                                    ${bug.attachment ? `
                                                    <div class="bug-info">
                                                        <label><i class="fas fa-paperclip"></i> Attachment</label>
                                                        <img src="${bug.attachment}" class="attachment-preview" alt="Bug Attachment">
                                                    </div>` : ''}
                                                </div>
                                                
                                                <div class="text-center">
                                                    <span class="view-more-btn" onclick="toggleBugDetails(this)">
                                                        <i class="fas fa-chevron-down"></i> View More
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                clearedBugsContainer.insertAdjacentHTML('beforeend', bugCard);
                            });

                            emptyState.style.display = 'none';
                        } else {
                            clearedBugsContainer.innerHTML = '';
                            emptyState.style.display = 'block';
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