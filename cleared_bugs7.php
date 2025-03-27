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
        "Session timed out due to inactivity on cleared bugs page",
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
        "Attempted to access cleared bugs page without login",
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
    "Accessed cleared bugs page",
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
    <title>Cleared Bug Reports</title>
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

        /* Bug Card Styling */
        .bug-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            background-color: #fff;
            padding: 15px;
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

        .bug-type-critical { background-color: #dc3545; }
        .bug-type-high { background-color: #fd7e14; }
        .bug-type-low { background-color: #ffc107; color: #212529; }

        .bug-card-body {
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
            display: none;
            margin-top: 10px;
        }

        .expandable-section.expanded {
            display: block;
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

        .cleared-badge {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Cleared Bug Reports</h4>
                <a href="bug_details.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Open Bugs
                </a>
            </div>

            <!-- Cleared Bugs Container -->
            <div class="row bug-cards-container" id="clearedBugsContainer">
                <?php
                $sql = "SELECT * FROM bug WHERE cleared_flag = 1 ORDER BY cleared_at DESC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    logUserAction(
                        $_SESSION['emp_id'] ?? null,
                        $_SESSION['user'],
                        'cleared_bugs_fetch_success',
                        "Fetched cleared bug reports",
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
                        $clearedDate = date('Y-m-d H:i', strtotime($row['cleared_at']));
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="bug-card">
                                <div class="bug-card-header">
								<i class="fas fa-bug text-primary me-2"></i>
                                    <h5><?= htmlspecialchars($row['Module_name']) ?></h5>
                                    <div>
                                        <span class="<?= $bugTypeClass ?>"><?= htmlspecialchars($row['bug_type']) ?></span>
                                        <span class="cleared-badge">Cleared</span>
                                    </div>
                                </div>
                                <div class="bug-card-body">
                                    <div class="bug-info">
                                        <label><i class="fas fa-align-left"></i> Description</label>
                                        <p><?= htmlspecialchars($row['description']) ?></p>
                                    </div>
                                    
                                    <div class="bug-info">
                                        <label><i class="fas fa-user"></i> Cleared By</label>
                                        <p><?= htmlspecialchars($row['cleared_by']) ?></p>
                                    </div>
                                    
                                    <div class="bug-info">
                                        <label><i class="far fa-calendar-alt"></i> Cleared At</label>
                                        <p><?= $clearedDate ?></p>
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
                                            </div>
                                            <div class="col-md-6">
                                                <div class="bug-info">
                                                    <label><i class="fas fa-code-branch"></i> Version</label>
                                                    <p><?= htmlspecialchars($row['Version']) ?></p>
                                                </div>
                                                <div class="bug-info">
                                                    <label><i class="fab fa-android"></i> Android Version</label>
                                                    <p><?= htmlspecialchars($row['android_version']) ?></p>
                                                </div>
                                            </div>
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

                                    <div class="view-more-btn" onclick="toggleExpandableSection('<?= $row['id'] ?>')">
                                        View More <i class="fas fa-chevron-down"></i>
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
                        'cleared_bugs_empty',
                        "No cleared bugs found",
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
                            <h4 class="mt-3">No Cleared Bug Reports</h4>
                            <p class="text-muted">No bugs have been marked as cleared yet.</p>
                          </div>';
                }
                $conn->close();
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
                        action: 'log_bug_details_expanded',
                        bug_id: id,
                        user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                        username: '<?php echo $_SESSION['user']; ?>'
                    }
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
                    action: 'log_admin_links_toggle',
                    state: adminLinks.style.display,
                    user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                    username: '<?php echo $_SESSION['user']; ?>'
                }
            });
        }

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
                    action: 'log_session_timeout_warning',
                    user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                    username: '<?php echo $_SESSION['user']; ?>',
                    page: 'cleared_bugs'
                }
            });
        }, sessionTimeout - popupTime);

        // Redirect to logout after timeout
        setTimeout(() => {
            // Log session timeout
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_session_timeout',
                    user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                    username: '<?php echo $_SESSION['user']; ?>',
                    page: 'cleared_bugs'
                },
                complete: function() {
                    window.location.href = 'logout.php';
                }
            });
        }, sessionTimeout);

        // Refresh cleared bugs every 30 seconds
        setInterval(function() {
            $.ajax({
                url: 'cleared_bugs_api.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        // Update the UI with new data
                        updateClearedBugsUI(data.data);
                        
                        // Log successful refresh
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_cleared_bugs_refresh',
                                count: data.data.length,
                                user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                                username: '<?php echo $_SESSION['user']; ?>'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Log refresh error
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_cleared_bugs_refresh_error',
                            error: error,
                            user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                            username: '<?php echo $_SESSION['user']; ?>'
                        }
                    });
                }
            });
        }, 30000);

        function updateClearedBugsUI(bugs) {
            const container = $('#clearedBugsContainer');
            const emptyState = $('.empty-state');
            
            if (bugs.length > 0) {
                container.empty();
                
                bugs.forEach(bug => {
                    const bugTypeClass = 'bug-type-' + bug.bug_type.toLowerCase();
                    const clearedDate = new Date(bug.cleared_at).toLocaleString();
                    
                    const bugCard = `
                        <div class="col-md-6 col-lg-4">
                            <div class="bug-card">
                                <div class="bug-card-header">
                                    <h5>${bug.Module_name}</h5>
                                    <div>
                                        <span class="${bugTypeClass}">${bug.bug_type}</span>
                                        <span class="cleared-badge">Cleared</span>
                                    </div>
                                </div>
                                <div class="bug-card-body">
                                    <div class="bug-info">
                                        <label><i class="fas fa-align-left"></i> Description</label>
                                        <p>${bug.description}</p>
                                    </div>
                                    
                                    <div class="bug-info">
                                        <label><i class="fas fa-user"></i> Cleared By</label>
                                        <p>${bug.cleared_by}</p>
                                    </div>
                                    
                                    <div class="bug-info">
                                        <label><i class="far fa-calendar-alt"></i> Cleared At</label>
                                        <p>${clearedDate}</p>
                                    </div>

                                    <div class="expandable-section" id="expandable_${bug.id}">
                                        <!-- Additional bug details here -->
                                    </div>

                                    <div class="view-more-btn" onclick="toggleExpandableSection('${bug.id}')">
                                        View More <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(bugCard);
                });
                
                emptyState.hide();
            } else {
                container.empty();
                emptyState.show();
            }
        }

        // Initialize the page
        $(document).ready(function() {
            // Log page load
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_page_load',
                    page: 'cleared_bugs',
                    user_id: '<?php echo $_SESSION['emp_id'] ?? ''; ?>',
                    username: '<?php echo $_SESSION['user']; ?>'
                }
            });
        });
    </script>
</body>
</html>