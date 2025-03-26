<?php
session_start();
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

// Assume logged-in user's name is stored in the session
$logged_in_user = $_SESSION['emp_name'] ?? 'Unknown';

// Fetch distinct products
$sql_products = "SELECT DISTINCT Product_name FROM testcase";
$result_products = $conn->query($sql_products);

// Fetch distinct versions
$sql_versions = "SELECT DISTINCT Version FROM testcase";
$result_versions = $conn->query($sql_versions);

// Preserve filter criteria after form submission
$selected_product = $_POST['product_name'] ?? $_SESSION['selected_product'] ?? '';
$selected_version = $_POST['version'] ?? $_SESSION['selected_version'] ?? '';

// Store filters in session
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['product_name'])) {
        $_SESSION['selected_product'] = $_POST['product_name'];
    }
    if (isset($_POST['version'])) {
        $_SESSION['selected_version'] = $_POST['version'];
    }
    
    // Store device name and Android version in session if provided
    if (!empty($_POST['device_name'])) {
        $_SESSION['device_name'] = $_POST['device_name'];
    }
    if (!empty($_POST['android_version'])) {
        $_SESSION['android_version'] = $_POST['android_version'];
    }
    
    if (isset($_POST['reset'])) {
        unset($_SESSION['device_name']);
        unset($_SESSION['android_version']);
        unset($_SESSION['selected_product']);
        unset($_SESSION['selected_version']);
        $selected_product = '';
        $selected_version = '';
    }
}

$device_name = $_SESSION['device_name'] ?? '';
$android_version = $_SESSION['android_version'] ?? '';

// Fetch folders for APK download
$folders = array_filter(glob('uploads/*'), 'is_dir');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Test Case</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            overflow-x: hidden;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
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
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .sidebar-container.collapsed {
            transform: translateX(-240px);
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
            min-height: 100vh;
            margin-left: 220px;
            transition: margin-left 0.3s ease;
        }
        .content-container.expanded {
            margin-left: 20px;
        }
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
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .filter-item {
            flex-grow: 1;
            min-width: 200px;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        .card {
            margin-bottom: 15px;
            border: 2px solid #007bff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .info-icon {
            color: #007bff;
            cursor: pointer;
        }
        .test-form {
            margin-top: auto;
        }
        .test-result {
            margin: 15px 0;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .pass-label {
            color: #198754;
        }
        .fail-label {
            color: #dc3545;
        }
        .submission-message {
            display: none;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .submission-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .submission-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s;
        }
        .submit-btn-container {
            margin-top: 15px;
        }
        .spinner-overlay.show {
            visibility: visible;
            opacity: 1;
        }
        .tooltip-inner {
            max-width: 300px;
            text-align: left;
        }
        .apk-download-modal .modal-dialog {
            max-width: 400px;
        }
        .apk-download-modal .card {
            border: none;
            box-shadow: none;
        }
        .bug-details-fail {
            background-color: #e7f1ff;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .bug-details-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: flex-end;
        }
        .bug-details-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .bug-details-row .form-group:last-child {
            flex: 0 0 200px;
        }
        .card-pass {
            animation: pulsePass 2s;
            border-color: #198754;
        }
        .card-fail {
            animation: pulseFail 2s;
            border-color: #dc3545;
        }
        @keyframes pulsePass {
            0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }
        @keyframes pulseFail {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .card-disabled {
            opacity: 0.8;
            background-color: #f8f9fa;
        }
        .tested-badge {
            display: none;
        }
        .sidebar-toggle {
            display: none;
            position: fixed;
            left: 20px;
            top: 20px;
            z-index: 1050;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
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
                display: block;
            }
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .bug-details-row {
                flex-direction: column;
            }
            .bug-details-row .form-group:last-child {
                flex: 1;
                width: 100%;
            }
        }
        
        @media (min-width: 768px) and (max-width: 991.98px) {
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .sidebar-container {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                border-radius: 0;
                margin-right: 0;
            }
            .content-container {
                margin-left: 220px;
            }
        }
        
        @media (min-width: 992px) {
            .col-md-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="spinner-overlay">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    <!-- APK Download Modal -->
    <div class="modal fade apk-download-modal" id="apkDownloadModal" tabindex="-1" aria-labelledby="apkDownloadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="apkDownloadModalLabel">Download APK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <form id="apkDownloadForm">
                            <div class="mb-3">
                                <select id="folderSelect" class="form-select">
                                    <option value="">Select Product</option>
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
                            <button type="button" id="downloadBtn" class="btn btn-primary w-100" disabled>
                                <i class="fas fa-download me-2"></i> Download
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
           
        <!-- Main Content -->
        <div class="content-container" id="contentContainer">
            <h3>Testing</h3>
            
            <!-- Notification area for submission feedback -->
            <div id="submission-message" class="submission-message"></div>
            
            <!-- Combined Filter Section with Device Info -->
            <div class="filter-container">
                <form id="filter-form" method="POST" class="w-100 d-flex flex-wrap gap-2 align-items-center">
                    <div class="filter-item">
                        <select name="product_name" id="product_name" required class="form-select form-select-sm">
                            <option value="">-- Select Product --</option>
                            <?php 
                            $result_products->data_seek(0);
                            while ($row = $result_products->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Product_name']); ?>" <?= ($selected_product == $row['Product_name']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Product_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <select name="version" id="version" required class="form-select form-select-sm">
                            <option value="">-- Select Version --</option>
                            <?php 
                            $result_versions->data_seek(0);
                            while ($row = $result_versions->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Version']); ?>" <?= ($selected_version == $row['Version']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Version']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <!-- Device Information (Entered once per session) -->
                    <div class="filter-item">
                        <input type="text" name="device_name" id="global_device_name" 
                               class="form-control form-control-sm" 
                               placeholder="Device Name" 
                               value="<?= htmlspecialchars($device_name); ?>"
                               required>
                    </div>
                    
                    <div class="filter-item">
                        <input type="text" name="android_version" id="global_android_version" 
                               class="form-control form-control-sm" 
                               placeholder="Android Version" 
                               value="<?= htmlspecialchars($android_version); ?>"
                               required>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <button type="submit" name="reset" class="btn btn-danger btn-sm">
                            <i class="fas fa-sync"></i> Reset
                        </button>
                        
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#apkDownloadModal">
                            <i class="fas fa-download"></i> Download APK
                        </button>
                    </div>
                </form>
            </div>

            <!-- Test Cases Section -->
            <div id="test-cases-container">
                <?php
                if (!empty($selected_product) && !empty($selected_version)) {
                    $sql = "SELECT * FROM testcase WHERE Product_name = ? AND Version = ? ORDER BY id ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $selected_product, $selected_version);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        echo "<h4 class='mt-3 mb-3'>Test Cases for $selected_product - $selected_version</h4>";
                        echo '<div class="row" id="test-cards">';
                        
                        while ($row = $result->fetch_assoc()) { 
                            $testcase_id = $row['id'];
                            $is_updated = !empty($row['tested_by_name']);
                            $testing_result = $row['testing_result'] ?? '';
                        ?>
                           <div class="col-md-4 mb-3">
        <div class="card h-100 <?= $is_updated ? 'card-disabled' : '' ?>" id="card-<?= $testcase_id ?>">
            <div class="card-header">
                <span>
                    <i class="fas fa-folder me-1"></i> 
                    <?= htmlspecialchars($row['Module_name']); ?> 
                    <span class="badge bg-secondary ms-1">ID: <?= $testcase_id ?></span>
                </span>
                <i class="fas fa-info-circle info-icon" 
                   data-bs-toggle="tooltip" 
                   data-bs-html="true" 
                   title="<strong>Description:</strong><br><?= htmlspecialchars($row['description']); ?>"></i>
            </div>
            
            <div class="card-body">
                <div class="test-info mb-3">
                    <p class="mb-2"><strong>Expected Result:</strong></p>
                    <p class="ms-2"><?= htmlspecialchars($row['expected_results']); ?></p>
                    
                    <p class="mb-2"><strong>Test Steps:</strong></p>
                    <p class="ms-2"><?= htmlspecialchars($row['test_steps']); ?></p>
                </div>
                
                <form class="test-form" data-id="<?= $testcase_id; ?>" action="update_testcases.php" method="POST" enctype="multipart/form-data" <?= $is_updated ? 'disabled' : '' ?>>
                    <input type="hidden" name="id" value="<?= $testcase_id; ?>">
                    <input type="hidden" name="tested_by_name" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                    <input type="hidden" name="tested_at" value="<?= date('Y-m-d H:i:s'); ?>">
                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($selected_product); ?>">
                    <input type="hidden" name="version" value="<?= htmlspecialchars($selected_version); ?>">
                    
                    <!-- Hidden device fields -->
                    <input type="hidden" name="device_name" id="device_name_<?= $testcase_id ?>" value="">
                    <input type="hidden" name="android_version" id="android_version_<?= $testcase_id ?>" value="">
                    
                    <div class="test-result">
                        <p class="mb-2"><strong>Testing Result:</strong></p>
                        <div class="radio-group">
                            <label class="pass-label">
                                <input type="radio" name="testing_result" value="Pass" class="me-1 result-radio" data-id="<?= $testcase_id ?>" <?= $is_updated ? 'disabled' : '' ?>>
                                <i class="fas fa-check-circle me-1"></i> Pass
                            </label>
                            <label class="fail-label">
                                <input type="radio" name="testing_result" value="Fail" class="me-1 result-radio" data-id="<?= $testcase_id ?>" <?= $is_updated ? 'disabled' : '' ?>>
                                <i class="fas fa-times-circle me-1"></i> Fail
                            </label>
                        </div>
                    </div>
                    
                    <div id="bug-details-<?= $testcase_id ?>" class="bug-details d-none">
                        <div class="bug-details-row">
                            <div class="form-group">
                                <label for="bug_type_<?= $testcase_id; ?>" class="form-label">Bug Type:</label>
                                <select id="bug_type_<?= $testcase_id; ?>" name="bug_type" class="form-select" required disabled>
                                    <option value="">Select Bug Type</option>
                                    <option value="Critical">Critical</option>
                                    <option value="High">High</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="file_attachment_<?= $testcase_id; ?>" class="form-label">Screenshot:</label>
                                <input type="file" id="file_attachment_<?= $testcase_id; ?>" name="file_attachment" class="form-control" disabled>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="actual_result_<?= $testcase_id; ?>" class="form-label">Actual Result:</label>
                            <textarea id="actual_result_<?= $testcase_id; ?>" name="actual_result" class="form-control" rows="3" required disabled></textarea>
                        </div>
                    </div>
                    
                    <div class="submit-btn-container">
                        <button type="submit" class="btn btn-primary w-100" <?= $is_updated ? 'disabled' : '' ?>>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div> 
                        <?php }
                        echo '</div>';
                    } else {
                        echo "<p class='alert alert-warning'>No test cases found for this selection.</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Toggle sidebar for mobile view
            $('#sidebarToggle').click(function() {
                $('#sidebarContainer').toggleClass('show');
            });
            
            // Close sidebar when clicking outside on mobile
            $(document).click(function(e) {
                if ($(window).width() < 768) {
                    if (!$(e.target).closest('#sidebarContainer').length && 
                        !$(e.target).is('#sidebarToggle') && 
                        $('#sidebarContainer').hasClass('show')) {
                        $('#sidebarContainer').removeClass('show');
                    }
                }
            });
            
            // APK Download functionality
            let versionMap = {}; // Stores file names mapped to version names

            $("#folderSelect").change(function() {
                let folder = $(this).val();
                let versionSelect = $("#versionSelect");
                let downloadBtn = $("#downloadBtn");

                versionSelect.html("<option value=''>Loading...</option>");
                versionSelect.prop("disabled", true);
                downloadBtn.prop("disabled", true);
                versionMap = {}; // Reset version mapping

                if (folder) {
                    $.get(`apk_download_api.php?fetch_versions=${folder}`, function(data) {
                        versionSelect.html("<option value=''>Select Version</option>");

                        data.forEach(item => {
                            versionMap[item.version] = item.filename; // Map version to filename
                            versionSelect.append(`<option value='${item.version}'>${item.version}</option>`);
                        });

                        versionSelect.prop("disabled", false);
                    });
                }
            });

            $("#versionSelect").change(function() {
                $("#downloadBtn").prop("disabled", !$(this).val());
            });

            $("#downloadBtn").click(function() {
                let folder = $("#folderSelect").val();
                let version = $("#versionSelect").val();
                let filename = versionMap[version]; // Get full filename based on version

                if (folder && filename) {
                    // Close the modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('apkDownloadModal'));
                    modal.hide();
                    
                    // Start the download
                    window.location.href = `uploads/${folder}/${filename}`;
                }
            });
            
            // Test Result Change Handling
            $(document).on('change', '.result-radio', function() {
                const testcaseId = $(this).data('id');
                const result = $(this).val();
                const bugDetails = $('#bug-details-' + testcaseId);
                
                const bugType = $(`#bug_type_${testcaseId}`);
                const actualResult = $(`#actual_result_${testcaseId}`);
                const fileAttachment = $(`#file_attachment_${testcaseId}`);
                
                if (result === 'Fail') {
                    bugDetails.slideDown();
                    $('#actual_result_' + testcaseId).prop('required', true);
                    
                    bugDetails.removeClass('d-none');
                    bugDetails.addClass('bug-details-fail');
                    
                    // Enable input fields
                    bugType.prop('disabled', false);
                    actualResult.prop('disabled', false);
                    fileAttachment.prop('disabled', false);
                    
                    // Clear existing values
                    bugType.val('');
                    actualResult.val('');
                    fileAttachment.val('');
                } else {
                    bugDetails.slideUp();
                    $('#actual_result_' + testcaseId).prop('required', false);
                    
                    bugDetails.addClass('d-none');
                    bugDetails.removeClass('bug-details-fail');
                    
                    // Disable and clear input fields
                    bugType.prop('disabled', true).val('');
                    actualResult.prop('disabled', true).val('');
                    fileAttachment.prop('disabled', true).val('');
                }
            });
            
            // Trigger the change event for any pre-selected "Fail" radio buttons
            $('.result-radio:checked').each(function() {
                if ($(this).val() === 'Fail') {
                    $(this).trigger('change');
                }
            });

            // Enhanced form validation
            $(document).on('submit', '.test-form', function(e) {
                e.preventDefault();
                const form = $(this);
                const testcaseId = form.data('id');
                const cardElement = $('#card-' + testcaseId);
                
                // Get the global device info
                const deviceName = $('#global_device_name').val();
                const androidVersion = $('#global_android_version').val();
                
                // Validate device info is entered
                if (!deviceName || !androidVersion) {
                    showAlert('danger', 'Please enter Device Name and Android Version before submitting test results');
                    $('#global_device_name').focus();
                    return;
                }
                
                // Validate test result is selected
                const selectedResult = form.find('input[name="testing_result"]:checked').val();
                if (!selectedResult) {
                    showAlert('danger', 'Please select a test result (Pass or Fail)');
                    return;
                }
                
                // Additional validation for failed tests
                if (selectedResult === 'Fail') {
                    const bugType = form.find('#bug_type_' + testcaseId).val();
                    const actualResult = form.find('#actual_result_' + testcaseId).val().trim();
                    
                    if (!bugType) {
                        showAlert('danger', 'Please select a bug type for failed tests');
                        form.find('#bug_type_' + testcaseId).focus();
                        return;
                    }
                    
                    if (!actualResult) {
                        showAlert('danger', 'Please describe the actual result for failed tests');
                        form.find('#actual_result_' + testcaseId).focus();
                        return;
                    }
                }
                
                // Set the device info in the hidden fields
                form.find('#device_name_' + testcaseId).val(deviceName);
                form.find('#android_version_' + testcaseId).val(androidVersion);
                
                // Show loading spinner
                $('.spinner-overlay').addClass('show');
                
                // Create FormData object from the form
                const formData = new FormData(this);
                formData.append('testing_result', selectedResult);
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Hide loading spinner
                        $('.spinner-overlay').removeClass('show');
                        
                        try {
                            // Parse the response
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            
                            if (data.status === 'success' || data.success) {
                                showAlert('success', data.message || 'Test case updated successfully');
                                
                                // Update the UI for this specific test case
                                updateTestedUI(cardElement, selectedResult);
                                
                                // If there's a next ID, scroll to it
                                if (data.next_id && data.next_id > 0) {
                                    const nextCard = $('#card-' + data.next_id);
                                    if (nextCard.length) {
                                        $('html, body').animate({
                                            scrollTop: nextCard.offset().top - 100
                                        }, 500);
                                    }
                                }
                            } else {
                                showAlert('danger', data.message || 'Error updating test case');
                            }
                        } catch (e) {
                            showAlert('danger', 'Invalid server response: ' + e.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('.spinner-overlay').removeClass('show');
                        showAlert('danger', 'Error updating test case: ' + error);
                    }
                });
            });

            // Update UI after successful submission
            function updateTestedUI(cardElement, result) {
                // Disable the form after successful submission
                cardElement.find('input, textarea, select, button').prop('disabled', true);
                
                // Add card-disabled class to gray out the card
                cardElement.addClass('card-disabled');
                
                // Reset form fields
                const form = cardElement.find('.test-form');
                form.find('.result-radio').prop('checked', false);
                form.find('#bug_type_' + form.data('id')).val('').prop('disabled', true);
                form.find('#actual_result_' + form.data('id')).val('').prop('disabled', true);
                form.find('#file_attachment_' + form.data('id')).val('').prop('disabled', true);
                form.find('.bug-details').addClass('d-none').removeClass('bug-details-fail');
                
                // Add animation class based on result
                if (result === 'Pass') {
                    cardElement.addClass('card-pass');
                } else {
                    cardElement.addClass('card-fail');
                }
                
                // Remove animation classes after animation completes
                setTimeout(function() {
                    cardElement.removeClass('card-pass card-fail');
                }, 2000);
            }

            function showAlert(type, message) {
                const alertDiv = $('#submission-message');
                alertDiv.removeClass('alert-success alert-danger')
                       .addClass(`alert-${type}`)
                       .text(message)
                       .fadeIn()
                       .delay(3000)
                       .fadeOut();
            }
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() >= 768) {
                    $('#sidebarContainer').removeClass('show');
                }
            });
        });
       
        // Function to toggle admin links visibility
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>