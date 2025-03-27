<?php
session_start();
include 'log_api.php';
include 'db_config.php';

// Set session timeout to 5 minutes (300 seconds)
$timeout = 300;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'session_timeout',
        "Session timed out due to inactivity on bug details page",
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
        "Attempted to access bug details page without login",
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

// Log successful page access
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'],
    'page_access',
    "Accessed bug details page",
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
    <title>Bug Reports</title>
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
            display: flex;
            flex-direction: column;
            height: 100%; /* Make all cards the same height */
            min-height: 400px; /* Set a minimum height */
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
            flex: 1;
            display: flex;
            flex-direction: column;
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
            margin-top: auto; /* Push to bottom */
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
            margin-top: auto; /* Push to bottom */
            padding-top: 10px;
        }
        
        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar-container">
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Open Bug Reports</h4>
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
                        $sql_products = "SELECT DISTINCT Product_name FROM bug WHERE cleared_flag = 0";
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
                        $sql_versions = "SELECT DISTINCT Version FROM bug WHERE cleared_flag = 0";
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
                        $sql_bug_types = "SELECT DISTINCT bug_type FROM bug WHERE cleared_flag = 0";
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

            <!-- Bug Reports Cards Container -->
            <div class="row bug-cards-container" id="bugCardsContainer">
                <?php
                $sql = "SELECT * FROM bug WHERE cleared_flag = 0 ORDER BY created_at DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    logUserAction(
                        $_SESSION['emp_id'] ?? null,
                        $_SESSION['user'],
                        'bug_fetch_success',
                        "Fetched open bug reports",
                        $_SERVER['REQUEST_URI'],
                        $_SERVER['REQUEST_METHOD'],
                        ['bug_count' => $result->num_rows],
                        200,
                        null,
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    );

                    while ($row = $result->fetch_assoc()) {
                        $bugTypeClass = '';
                        switch ($row['bug_type']) {
                            case 'Critical': $bugTypeClass = 'bug-type-critical'; break;
                            case 'High': $bugTypeClass = 'bug-type-high'; break;
                            case 'Low': $bugTypeClass = 'bug-type-low'; break;
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 bug-card-col" 
                             data-product="<?= htmlspecialchars($row['Product_name']) ?>" 
                             data-version="<?= htmlspecialchars($row['Version']) ?>" 
                             data-bug-type="<?= htmlspecialchars($row['bug_type']) ?>">
                            <div class="bug-card" id="card_<?= $row['id'] ?>" data-testcase-id="<?= $row['testcase_id'] ?>">
                                <div class="bug-card-header">
                                    <h5><?= htmlspecialchars($row['Module_name']) ?></h5>
                                    <span class="bug-type <?= $bugTypeClass ?>"><?= htmlspecialchars($row['bug_type']) ?></span>
                                </div>
                                <div class="bug-card-body">
                                    <div class="bug-info">
                                        <label><i class="fas fa-align-left"></i> Description</label>
                                        <p><?= htmlspecialchars($row['description']) ?></p>
                                    </div>
                                    <div class="bug-info">
                                        <label><i class="fas fa-list-ol"></i> Test Steps</label>
                                        <p><?= htmlspecialchars($row['test_steps']) ?></p>
                                    </div>
                                    <div class="bug-info">
                                        <label><i class="fas fa-check-circle"></i> Expected Result</label>
                                        <p><?= htmlspecialchars($row['expected_results']) ?></p>
                                    </div>
                                    <div class="bug-info">
                                        <label><i class="fas fa-times-circle"></i> Actual Result</label>
                                        <p><?= htmlspecialchars($row['actual_result']) ?></p>
                                    </div>

                                    <div class="expandable-section" id="expandable_<?= $row['id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bug-info">
                                                    <label><i class="fas fa-tag"></i> Product</label>
                                                    <p><?= htmlspecialchars($row['Product_name']) ?></p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-mobile-alt"></i> Device</label>
                                                    <p><?= htmlspecialchars($row['device_name']) ?></p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fab fa-android"></i> Android Version</label>
                                                    <p><?= htmlspecialchars($row['android_version']) ?></p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="bug-info">
                                                    <label><i class="fas fa-code-branch"></i> Version</label>
                                                    <p><?= htmlspecialchars($row['Version']) ?></p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fas fa-user"></i> Tested By</label>
                                                    <p><?= htmlspecialchars($row['tested_by_name']) ?></p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="far fa-calendar-alt"></i> Tested At</label>
                                                    <p><?= date('Y-m-d H:i', strtotime($row['tested_at'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($row['file_attachment'])): ?>
                                            <div class="bug-info">
                                                <label><i class="fas fa-paperclip"></i> Attachment</label>
                                                <?php
                                                $file_url = htmlspecialchars($row['file_attachment'], ENT_QUOTES, 'UTF-8');
                                                $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
                                                
                                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                                    echo '<a href="'.$file_url.'" class="view-attachment-btn" target="_blank"><i class="fas fa-eye"></i> View Image</a>';
                                                } elseif (in_array($file_extension, ['mp4', 'webm', 'ogg'])) {
                                                    echo '<a href="'.$file_url.'" class="view-attachment-btn" target="_blank"><i class="fas fa-play"></i> View Video</a>';
                                                } else {
                                                    echo '<a href="'.$file_url.'" class="view-attachment-btn" target="_blank"><i class="fas fa-file"></i> View File</a>';
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="bug-card-footer">
                                        <div class="view-more-btn" onclick="toggleExpandableSection('<?= $row['id'] ?>')">
                                            View More <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <button class="btn btn-danger clear-btn" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-check-circle"></i> Mark as Cleared
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    logUserAction(
                        $_SESSION['emp_id'] ?? null,
                        $_SESSION['user'],
                        'bug_fetch_empty',
                        "No open bugs found",
                        $_SERVER['REQUEST_URI'],
                        $_SERVER['REQUEST_METHOD'],
                        null,
                        200,
                        null,
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    );
                    
                    echo '<div class="col-12 empty-state">
                            <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                            <h4 class="mt-3">No Open Bug Reports</h4>
                            <p class="text-muted">All bugs have been cleared.</p>
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Session Timeout Modal -->
    <div class="modal fade" id="sessionPopup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Expiring Soon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
        // Toggle expandable section
        function toggleExpandableSection(id) {
            const section = document.getElementById('expandable_' + id);
            const btn = section.closest('.bug-card-body').querySelector('.view-more-btn');
            
            section.classList.toggle('expanded');
            if (section.classList.contains('expanded')) {
                btn.innerHTML = 'View Less <i class="fas fa-chevron-up"></i>';
                
                // Log section expansion
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'bug_details_expanded',
                        description: 'Expanded bug details',
                        bug_id: id
                    },
                    dataType: 'json'
                });
            } else {
                btn.innerHTML = 'View More <i class="fas fa-chevron-down"></i>';
            }
        }

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
                    description: 'Loaded bug details page'
                },
                dataType: 'json'
            });

            // Handle clear button clicks
            $(document).on('click', '.clear-btn', function() {
                const bugId = $(this).data('id');
                const testcaseId = $(this).closest('.bug-card').data('testcase-id');
                const card = $(this).closest('.bug-card-col');
                
                if (!bugId) {
                    alert('Error: Bug ID is missing');
                    
                    // Log missing bug ID error
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'bug_clear_error',
                            description: 'Missing bug ID when attempting to clear bug'
                        },
                        dataType: 'json'
                    });
                    return;
                }

                if (confirm("Are you sure you want to mark this bug as cleared?")) {
                    // Log bug clear attempt
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'bug_clear_attempt',
                            description: 'Attempting to clear bug',
                            bug_id: bugId,
                            testcase_id: testcaseId
                        },
                        dataType: 'json'
                    });

                    $.ajax({
                        url: 'bug_reports_api.php',
                        type: 'POST',
                        data: {
                            action: 'clear_bug',
                            id: bugId,
                            testcase_id: testcaseId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.status === 'success') {
                                // Remove the card with animation
                                card.css({
                                    'opacity': '0',
                                    'transform': 'scale(0.9)',
                                    'transition': 'all 0.3s ease'
                                });
                                
                                setTimeout(() => {
                                    card.remove();
                                    
                                    // Show empty state if no bugs left
                                    if ($('.bug-card-col').length === 0) {
                                        $('#bugCardsContainer').html(`
                                            <div class="col-12 empty-state">
                                                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                                                <h4 class="mt-3">No Open Bug Reports</h4>
                                                <p class="text-muted">All bugs have been cleared.</p>
                                            </div>
                                        `);
                                    }
                                }, 300);
                                
                                // Log successful bug clearance
                                $.ajax({
                                    url: 'log_api.php',
                                    type: 'POST',
                                    data: {
                                        action: 'log_client_action',
                                        action_type: 'bug_clear_success',
                                        description: 'Successfully cleared bug',
                                        bug_id: bugId,
                                        testcase_id: testcaseId
                                    },
                                    dataType: 'json'
                                });
                            } else {
                                const errorMsg = response && response.message 
                                    ? response.message 
                                    : 'Unknown error occurred';
                                alert('Error: ' + errorMsg);
                                
                                // Log bug clear failure
                                $.ajax({
                                    url: 'log_api.php',
                                    type: 'POST',
                                    data: {
                                        action: 'log_client_action',
                                        action_type: 'bug_clear_failed',
                                        description: errorMsg,
                                        bug_id: bugId,
                                        testcase_id: testcaseId,
                                        error: errorMsg
                                    },
                                    dataType: 'json'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred. Please check console for details.');
                            console.error('AJAX Error:', status, error);
                            
                            // Log AJAX error for bug clearance
                            $.ajax({
                                url: 'log_api.php',
                                type: 'POST',
                                data: {
                                    action: 'log_client_action',
                                    action_type: 'bug_clear_error',
                                    description: 'AJAX error when clearing bug',
                                    bug_id: bugId,
                                    testcase_id: testcaseId,
                                    error: error
                                },
                                dataType: 'json'
                            });
                        }
                    });
                } else {
                    // Log bug clear cancellation
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'bug_clear_cancelled',
                            description: 'User cancelled bug clearance',
                            bug_id: bugId,
                            testcase_id: testcaseId
                        },
                        dataType: 'json'
                    });
                }
            });

            // Filter functionality
            $('#applyFilter').click(function() {
                // Log filter application
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'bug_filter_applied',
                        description: 'Applied bug filters',
                        filters: {
                            product: $('#filterProduct').val(),
                            version: $('#filterVersion').val(),
                            bug_type: $('#filterBugType').val()
                        }
                    },
                    dataType: 'json'
                });
                
                applyFilters();
            });
            
            $('#resetFilter').click(function() {
                // Log filter reset
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'bug_filter_reset',
                        description: 'Reset bug filters'
                    },
                    dataType: 'json'
                });
                
                resetFilters();
            });

            function applyFilters() {
                const productFilter = $('#filterProduct').val();
                const versionFilter = $('#filterVersion').val();
                const bugTypeFilter = $('#filterBugType').val();

                let hasVisibleCards = false;

                $('.bug-card-col').each(function() {
                    const cardProduct = $(this).data('product');
                    const cardVersion = $(this).data('version');
                    const cardBugType = $(this).data('bug-type');

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

                    if (showCard) {
                        $(this).show();
                        hasVisibleCards = true;
                    } else {
                        $(this).hide();
                    }
                });

                checkEmptyState();
            }

            function resetFilters() {
                $('#filterProduct').val('');
                $('#filterVersion').val('');
                $('#filterBugType').val('');

                $('.bug-card-col').show();
                checkEmptyState();
            }

            function checkEmptyState() {
                const visibleCards = $('.bug-card-col:visible').length;
                
                if (visibleCards === 0) {
                    if ($('.empty-state').length === 0) {
                        $('#bugCardsContainer').append(`
                            <div class="col-12 empty-state">
                                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                                <h4 class="mt-3">No Open Bug Reports</h4>
                                <p class="text-muted">No bugs match the current filters.</p>
                            </div>
                        `);
                    }
                    
                    // Log empty state after filtering
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'bug_filter_empty',
                            description: 'No bugs match current filters',
                            filters: {
                                product: $('#filterProduct').val(),
                                version: $('#filterVersion').val(),
                                bug_type: $('#filterBugType').val()
                            }
                        },
                        dataType: 'json'
                    });
                } else {
                    $('.empty-state').remove();
                }
            }

            // Initial check
            checkEmptyState();
            
            // Session timeout handling
            const sessionTimeout = 5 * 60 * 1000; // 5 minutes
            const popupTime = 2 * 60 * 1000; // Show popup 2 minutes before timeout

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
                        description: 'Session timeout warning shown on bug details page'
                    },
                    dataType: 'json'
                });
            }, sessionTimeout - popupTime);

            // Redirect to logout after timeout
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, sessionTimeout);
        });
    </script>
</body>
</html>