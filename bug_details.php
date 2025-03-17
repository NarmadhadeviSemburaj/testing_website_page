<?php
session_start(); // Start the session
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

include 'db_config.php';

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Failed Test Cases</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
         /* Your existing CSS styles */
        /* ... (Copy all your CSS styles here) ... */
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
                <h4>Bug Reports</h4> 
                <!-- Moved to top - View Cleared Bugs button -->
                <a href="cleared_bugs7.php" class="btn btn-success">
                    <i class="fas fa-history"></i> View Cleared Bugs
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-row">
                <div class="form-group">
                    <label for="filterProduct">Product:</label>
                    <select id="filterProduct" class="form-select">
                        <option value="">All Products</option>
                        <?php
                        $sql_products = "SELECT DISTINCT Product_name FROM testcase";
                        $result_products = $conn->query($sql_products);
                        while ($row = $result_products->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($row['Product_name']); ?>">
                                <?php echo htmlspecialchars($row['Product_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterVersion">Version:</label>
                    <select id="filterVersion" class="form-select">
                        <option value="">All Versions</option>
                        <?php
                        $sql_versions = "SELECT DISTINCT Version FROM testcase";
                        $result_versions = $conn->query($sql_versions);
                        while ($row = $result_versions->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($row['Version']); ?>">
                                <?php echo htmlspecialchars($row['Version']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterBugType">Bug Type:</label>
                    <select id="filterBugType" class="form-select">
                        <option value="">All Bug Types</option>
                        <?php
                        $sql_bug_types = "SELECT DISTINCT bug_type FROM testcase WHERE bug_type IS NOT NULL";
                        $result_bug_types = $conn->query($sql_bug_types);
                        while ($row = $result_bug_types->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($row['bug_type']); ?>">
                                <?php echo htmlspecialchars($row['bug_type']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button id="applyFilter" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button id="resetFilter" class="btn btn-secondary">
                    <i class="fas fa-sync"></i> Reset
                </button>
            </div>

            <!-- Failed Test Cases Cards Container -->
            <div class="row bug-cards-container" id="bugCardsContainer">
                <?php
                $sql = "SELECT * FROM testcase WHERE testing_result = 'Fail'";
                $result = $conn->query($sql);

                while ($row = $result->fetch_assoc()) {
                    // Determine bug type class for styling
                    $bugTypeClass = '';
                    switch ($row['bug_type']) {
                        case 'Critical':
                            $bugTypeClass = 'bug-type-critical';
                            break;
                        case 'High':
                            $bugTypeClass = 'bug-type-high';
                            break;
                        case 'Low':
                            $bugTypeClass = 'bug-type-low';
                            break;
                        default:
                            $bugTypeClass = '';
                    }
                ?>
                    <div class="col-md-6 col-lg-4 bug-card-col" 
                         data-product="<?= htmlspecialchars($row['Product_name']); ?>" 
                         data-version="<?= htmlspecialchars($row['Version']); ?>" 
                         data-bug-type="<?= htmlspecialchars($row['bug_type']); ?>">
                        <div class="bug-card" id="card_<?= $row['id']; ?>">
                            <div class="bug-card-header">
                                <h5><?= htmlspecialchars($row['Module_name']); ?></h5>
                                <span class="bug-type <?= $bugTypeClass; ?>"><?= htmlspecialchars($row['bug_type']); ?></span>
                            </div>
                            <div class="bug-card-body">
                                <!-- Default visible content -->
                                <div class="bug-info">
                                    <label><i class="fas fa-align-left"></i> Description</label>
                                    <p><?= htmlspecialchars($row['description']); ?></p>
                                </div>
                                <div class="bug-info">
                                    <label><i class="fas fa-list-ol"></i> Test Steps</label>
                                    <p><?= htmlspecialchars($row['test_steps']); ?></p>
                                </div>
                                <div class="bug-info">
                                    <label><i class="fas fa-check-circle"></i> Expected Result</label>
                                    <p><?= htmlspecialchars($row['expected_results']); ?></p>
                                </div>
                                <div class="bug-info">
                                    <label><i class="fas fa-times-circle"></i> Actual Result</label>
                                    <p><?= htmlspecialchars($row['actual_result']); ?></p>
                                </div>

                                <!-- Expandable section with two-column layout -->
                                <div class="expandable-section" id="expandable_<?= $row['id']; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="bug-info">
                                                <label><i class="fas fa-tag"></i> Product</label>
                                                <p><?= htmlspecialchars($row['Product_name']); ?></p>
                                            </div>
                                            <div class="bug-info">
                                                <label><i class="fas fa-mobile-alt"></i> Device</label>
                                                <p><?= htmlspecialchars($row['device_name']); ?></p>
                                            </div>
                                            <div class="bug-info">
                                                <label><i class="fab fa-android"></i> Android Version</label>
                                                <p><?= htmlspecialchars($row['android_version']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bug-info">
                                                <label><i class="fas fa-code-branch"></i> Version</label>
                                                <p><?= htmlspecialchars($row['Version']); ?></p>
                                            </div>
                                            <div class="bug-info">
                                                <label><i class="fas fa-user"></i> Tested By</label>
                                                <p><?= htmlspecialchars($row['tested_by_name']); ?></p>
                                            </div>
                                            <div class="bug-info">
                                                <label><i class="far fa-calendar-alt"></i> Tested At</label>
                                                <p><?= date('Y-m-d H:i', strtotime($row['tested_at'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($row['file_attachment'])): ?>
                                        <div class="bug-info">
                                            <label><i class="fas fa-paperclip"></i> Attachment</label>
                                            <?php
                                            $file_url = htmlspecialchars($row['file_attachment'], ENT_QUOTES, 'UTF-8');
                                            $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
                                            
                                            $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                            $video_extensions = ['mp4', 'webm', 'ogg'];
                                            
                                            if (in_array($file_extension, $image_extensions)) {
                                                echo '<a href="' . $file_url . '" class="view-attachment-btn" target="_blank">
                                                        <i class="fas fa-eye"></i> View Image
                                                      </a>';
                                            } elseif (in_array($file_extension, $video_extensions)) {
                                                echo '<a href="' . $file_url . '" class="view-attachment-btn" target="_blank">
                                                        <i class="fas fa-play"></i> View Video
                                                      </a>';
                                            } else {
                                                echo '<a href="' . $file_url . '" class="view-attachment-btn" target="_blank">
                                                        <i class="fas fa-file"></i> View File
                                                      </a>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- View More Button and Mark as Cleared Button -->
                                <div class="bug-card-footer">
                                    <div class="view-more-btn" onclick="toggleExpandableSection(<?= $row['id']; ?>)">
                                        View More <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <button class="btn btn-danger clear-btn" data-id="<?= $row['id']; ?>">
                                        <i class="fas fa-check-circle"></i> Mark as Cleared
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <!-- Empty state message when no bugs match filters -->
            <div id="emptyState" class="text-center py-5" style="display:none;">
                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                <h4 class="mt-3">No Bug Reports Found</h4>
                <p class="text-muted">No bug reports match your current filter criteria.</p>
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

            // Toggle expandable section
            function toggleExpandableSection(id) {
                const expandableSection = document.getElementById(`expandable_${id}`);
                const viewMoreBtn = expandableSection.previousElementSibling.querySelector('.view-more-btn');

                if (expandableSection.classList.contains('expanded')) {
                    expandableSection.classList.remove('expanded');
                    viewMoreBtn.innerHTML = 'View More <i class="fas fa-chevron-down"></i>';
                } else {
                    expandableSection.classList.add('expanded');
                    viewMoreBtn.innerHTML = 'View Less <i class="fas fa-chevron-up"></i>';
                }
            }

            // Filter functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Handle clear button clicks
                document.querySelectorAll('.clear-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const testCaseId = this.getAttribute('data-id');
                        
                        if (confirm("Are you sure you want to mark this bug as cleared?")) {
                            // Send Ajax request to bug_reports_api.php
                            fetch('bug_reports_api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ id: testCaseId }),
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Remove the card with animation
                                    const card = document.getElementById('card_' + testCaseId);
                                    card.style.opacity = '0';
                                    card.style.transform = 'scale(0.8)';
                                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                                    
                                    setTimeout(() => {
                                        const cardCol = card.closest('.bug-card-col');
                                        if (cardCol) {
                                            cardCol.remove();
                                            checkEmptyState();
                                        }
                                    }, 300);
                                } else {
                                    alert('Error updating test case: ' + data.message);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                        }
                    });
                });

                // Filter functionality
                const applyFilterBtn = document.getElementById('applyFilter');
                const resetFilterBtn = document.getElementById('resetFilter');

                applyFilterBtn.addEventListener('click', applyFilters);
                resetFilterBtn.addEventListener('click', resetFilters);

                function applyFilters() {
                    const productFilter = document.getElementById('filterProduct').value;
                    const versionFilter = document.getElementById('filterVersion').value;
                    const bugTypeFilter = document.getElementById('filterBugType').value;

                    document.querySelectorAll('.bug-card-col').forEach(card => {
                        const cardProduct = card.getAttribute('data-product');
                        const cardVersion = card.getAttribute('data-version');
                        const cardBugType = card.getAttribute('data-bug-type');

                        let showCard = true;

                        if (productFilter && cardProduct !== productFilter) {
                            showCard = false;
                        }

                        if (versionFilter && cardVersion !== versionFilter) {
                            showCard = false;
                        }

                        if (bugTypeFilter && cardBugType !== bugTypeFilter) {
                            showCard = false;
                        }

                        card.style.display = showCard ? '' : 'none';
                    });

                    checkEmptyState();
                }

                function resetFilters() {
                    document.getElementById('filterProduct').value = '';
                    document.getElementById('filterVersion').value = '';
                    document.getElementById('filterBugType').value = '';

                    document.querySelectorAll('.bug-card-col').forEach(card => {
                        card.style.display = '';
                    });

                    checkEmptyState();
                }

                function checkEmptyState() {
                    const visibleCards = document.querySelectorAll('.bug-card-col[style=""]').length + 
                                         document.querySelectorAll('.bug-card-col:not([style])').length;

                    const emptyState = document.getElementById('emptyState');

                    if (visibleCards === 0) {
                        emptyState.style.display = 'block';
                    } else {
                        emptyState.style.display = 'none';
                    }
                }

                // Initial check
                checkEmptyState();
            });
        </script>
    </body>
</html>




      