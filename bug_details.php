<?php
session_start();
// Set session timeout to 5 minutes (300 seconds)
$timeout = 300;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
$_SESSION['last_activity'] = time();

include 'db_config.php';
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
            height: 100%;
            min-height: 400px;
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
            background-color: #dc3545;
        }

        .bug-type-high {
            background-color: #fd7e14;
        }

        .bug-type-low {
            background-color: #ffc107;
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
            display: none;
            margin-top: auto;
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

        .bug-info i {
            color: #007bff;
        }

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

        .bug-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }

        /* Add space between cards */
        .bug-cards-container {
            gap: 20px;
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
            .col-md-6, .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .col-lg-4 {
                flex: 0 0 calc(50% - 10px);
                max-width: calc(50% - 10px);
            }
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
        }
        
        @media (min-width: 1200px) {
            .col-lg-4 {
                flex: 0 0 calc(33.333333% - 14px);
                max-width: calc(33.333333% - 14px);
            }
            .sidebar-toggle {
                display: none;
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
        <div class="content-container" id="contentContainer">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Bug Reports</h4>
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
                        $sql_products = "SELECT DISTINCT Product_name FROM bug";
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
                        $sql_versions = "SELECT DISTINCT Version FROM bug";
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
                        $sql_bug_types = "SELECT DISTINCT bug_type FROM bug";
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
                    while ($row = $result->fetch_assoc()) {
                        $bugTypeClass = '';
                        switch ($row['bug_type']) {
                            case 'Critical': $bugTypeClass = 'bug-type-critical'; break;
                            case 'High': $bugTypeClass = 'bug-type-high'; break;
                            case 'Low': $bugTypeClass = 'bug-type-low'; break;
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4 bug-card-col" 
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

                                    <!-- Expandable section -->
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

                                    <!-- View More Button and Mark as Cleared Button -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle expandable section
        function toggleExpandableSection(id) {
            const section = document.getElementById('expandable_' + id);
            const btn = section.closest('.bug-card-body').querySelector('.view-more-btn');
            
            section.classList.toggle('expanded');
            if (section.classList.contains('expanded')) {
                btn.innerHTML = 'View Less <i class="fas fa-chevron-up"></i>';
            } else {
                btn.innerHTML = 'View More <i class="fas fa-chevron-down"></i>';
            }
        }

        // Toggle admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }

        // Toggle sidebar
        $(document).ready(function() {
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                $('#sidebarContainer').toggleClass('show');
            });

            // Close sidebar when clicking outside on mobile
            $(document).click(function(e) {
                if ($(window).width() < 1200) { // Changed from 768 to 1200
                    if (!$(e.target).closest('#sidebarContainer').length && 
                        !$(e.target).is('#sidebarToggle') && 
                        $('#sidebarContainer').hasClass('show')) {
                        $('#sidebarContainer').removeClass('show');
                    }
                }
            });

            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() >= 1200) {
                    $('#sidebarContainer').removeClass('show');
                }
            });

            // Handle clear button clicks
            $(document).on('click', '.clear-btn', function() {
                const bugId = $(this).data('id');
                const testcaseId = $(this).closest('.bug-card').data('testcase-id');
                
                if (!bugId) {
                    alert('Error: Bug ID is missing');
                    return;
                }

                if (confirm("Are you sure you want to mark this bug as cleared?")) {
                    $.ajax({
                        url: 'bug_reports_api.php',
                        type: 'POST',
                        data: {
                            action: 'clear_bug',
                            id: bugId,
                            testcase_id: testcaseId
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 'success') {
                                const card = $('#card_' + bugId);
                                card.css({
                                    'opacity': '0',
                                    'transform': 'scale(0.9)',
                                    'transition': 'all 0.3s ease'
                                });
                                
                                setTimeout(() => {
                                    card.closest('.bug-card-col').remove();
                                    
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
                            } else {
                                alert('Error: ' + data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred. Please try again.');
                            console.error(error);
                        }
                    });
                }
            });

            // Filter functionality
            $('#applyFilter').click(applyFilters);
            $('#resetFilter').click(resetFilters);

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
                const emptyState = $('#emptyState');
                const noBugsMessage = $('.empty-state').not('#emptyState');

                if (visibleCards === 0) {
                    emptyState.show();
                    if (noBugsMessage.length) noBugsMessage.hide();
                } else {
                    emptyState.hide();
                    if (noBugsMessage.length) noBugsMessage.hide();
                }
            }

            // Initial check
            checkEmptyState();
        });
    </script>
</body>
</html>
