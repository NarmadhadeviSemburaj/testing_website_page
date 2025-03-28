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
        "Session timed out due to inactivity on test case update page",
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
        "Attempted to access test case update page without login",
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
    "Accessed test case update page",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

$conn = new mysqli("localhost", "root", "", "testing_db");
if ($conn->connect_error) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'db_connection_error',
        "Database connection failed: " . $conn->connect_error,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
   
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
       
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'filter_selection',
            "Selected product filter: " . $_POST['product_name'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['product_name' => $_POST['product_name']],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }
   
    if (isset($_POST['version'])) {
        $_SESSION['selected_version'] = $_POST['version'];
       
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'filter_selection',
            "Selected version filter: " . $_POST['version'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['version' => $_POST['version']],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }
   
    // Store device name and Android version in session if provided
    if (!empty($_POST['device_name'])) {
        $_SESSION['device_name'] = $_POST['device_name'];
       
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'device_info_update',
            "Updated device name: " . $_POST['device_name'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['device_name' => $_POST['device_name']],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }
   
    if (!empty($_POST['android_version'])) {
        $_SESSION['android_version'] = $_POST['android_version'];
       
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'device_info_update',
            "Updated Android version: " . $_POST['android_version'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            ['android_version' => $_POST['android_version']],
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
    }
   
    if (isset($_POST['reset'])) {
        logUserAction(
            $_SESSION['emp_id'] ?? null,
            $_SESSION['user'],
            'filter_reset',
            "Reset all filters",
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            null,
            200,
            null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
       
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
        .spinner-overlay.show {
            visibility: visible;
            opacity: 1;
        }
        .tooltip-inner {
            max-width: 300px;
            text-align: left;
        }
		/* Add this to your CSS section */
.bug-details-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
    align-items: flex-end;
}

.bug-details-row .form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 0;
}

.bug-details-row .form-group.file-group {
    flex: 1;
    min-width: 250px;
}

.bug-details-row .form-group label {
    display: block;
    margin-bottom: 5px;
}

.upload-progress {
    margin-top: 5px;
}

/* For mobile devices */
@media (max-width: 767.98px) {
    .bug-details-row {
        flex-direction: row;
    }
    f
    .bug-details-row .form-group {
        flex: 1 0 100%;
    }
    
    .bug-details-row .form-group.file-group {
        flex: 1 0 100%;
    }
}
        .bug-details-fail {
            background-color: #e7f1ff;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .
        .card-pass {
            border-color: #198754 !important;
            animation: pulsePass 2s;
        }
        .card-fail {
            border-color: #dc3545 !important;
            animation: pulseFail 2s;
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
        /* Emoji animations */
        .emoji-animation {
            position: absolute;
            z-index: 1000;
            font-size: 24px;
            pointer-events: none;
            animation: float-up 3s ease-in forwards;
            opacity: 0;
        }
        @keyframes float-up {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        .pass-emojis {
            color: #198754;
        }
        .fail-emojis {
            color: #dc3545;
        }
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
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
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
            <h3>Testing</h3>
           
            <!-- Notification area for submission feedback -->
            <div id="submission-message" class="submission-message"></div>
           
            <!-- Combined Filter Section with Device Info -->
            <div class="filter-container">
                <form id="filter-form" method="POST" class="w-100 d-flex flex-wrap gap-2 align-items-center">
                    <div class="filter-item">
                        <select name="product_name" id="product_name" class="form-select form-select-sm">
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
                        <select name="version" id="version" class="form-select form-select-sm">
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
                               value="<?= htmlspecialchars($device_name); ?>">
                    </div>
                   
                    <div class="filter-item">
                        <input type="text" name="android_version" id="global_android_version"
                               class="form-control form-control-sm"
                               placeholder="Android Version"
                               value="<?= htmlspecialchars($android_version); ?>">
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
                        logUserAction(
                            $_SESSION['emp_id'] ?? null,
                            $_SESSION['user'],
                            'testcase_fetch_success',
                            "Fetched test cases for product: $selected_product, version: $selected_version",
                            $_SERVER['REQUEST_URI'],
                            $_SERVER['REQUEST_METHOD'],
                            ['testcase_count' => $result->num_rows],
                            200,
                            null,
                            $_SERVER['REMOTE_ADDR'],
                            $_SERVER['HTTP_USER_AGENT']
                        );
                       
                        echo "<h4 class='mt-3 mb-3'>Test Cases for $selected_product - $selected_version</h4>";
                        echo '<div class="row" id="test-cards">';
                       
                        while ($row = $result->fetch_assoc()) {
                            $testcase_id = $row['id'];
                            $testing_result = $row['testing_result'] ?? '';
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100" id="card-<?= $testcase_id ?>">
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
                                   
                                    <form class="test-form" data-id="<?= $testcase_id; ?>" action="update_testcases.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $testcase_id; ?>">
                                        <input type="hidden" name="tested_by_name" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($selected_product); ?>">
                                        <input type="hidden" name="version" value="<?= htmlspecialchars($selected_version); ?>">
                                       
                                        <!-- Hidden device fields -->
                                        <input type="hidden" name="device_name" id="device_name_<?= $testcase_id ?>" value="">
                                        <input type="hidden" name="android_version" id="android_version_<?= $testcase_id ?>" value="">
                                       
                                        <div class="test-result">
                                            <p class="mb-2"><strong>Testing Result:</strong></p>
                                            <div class="radio-group">
                                                <label class="pass-label">
                                                    <input type="radio" name="testing_result" value="pass" class="me-1 result-radio" data-id="<?= $testcase_id ?>" <?= ($testing_result === 'pass') ? 'checked' : '' ?>>
                                                    <i class="fas fa-check-circle me-1"></i> Pass
                                                </label>
                                                <label class="fail-label">
                                                    <input type="radio" name="testing_result" value="fail" class="me-1 result-radio" data-id="<?= $testcase_id ?>" <?= ($testing_result === 'fail') ? 'checked' : '' ?>>
                                                    <i class="fas fa-times-circle me-1"></i> Fail
                                                </label>
                                            </div>
                                        </div>
                                         <div id="bug-details-<?= $testcase_id ?>" class="bug-details <?= ($testing_result === 'fail') ? '' : 'd-none' ?>">
											<div class="bug-details-row">
												<div class="form-group">
													<label for="bug_type_<?= $testcase_id; ?>" class="form-label">Bug Type:</label>
													<select id="bug_type_<?= $testcase_id; ?>" name="bug_type" class="form-select" <?= ($testing_result === 'fail') ? '' : 'disabled' ?>>
														<option value="">Select Bug Type</option>
														<option value="Critical" <?= ($row['bug_type'] ?? '' === 'Critical') ? 'selected' : '' ?>>Critical</option>
														<option value="High" <?= ($row['bug_type'] ?? '' === 'High') ? 'selected' : '' ?>>High</option>
														<option value="Low" <?= ($row['bug_type'] ?? '' === 'Low') ? 'selected' : '' ?>>Low</option>
													</select>
												</div>
											   
												<div class="form-group file-group">
													<label for="file_attachment_<?= $testcase_id; ?>" class="form-label">Attachment:</label>
													<input type="file" id="file_attachment_<?= $testcase_id; ?>" name="file_attachment" class="form-control" <?= ($testing_result === 'fail') ? '' : 'disabled' ?>>
													<div class="upload-progress">
														<div class="progress">
															<div class="progress-bar" role="progressbar" style="width: 0%"></div>
														</div>
														<div class="file-info small text-muted"></div>
													</div>
												</div>
											</div>
										   
											<div class="form-group">
												<label for="actual_result_<?= $testcase_id; ?>" class="form-label">Actual Result:</label>
												<textarea id="actual_result_<?= $testcase_id; ?>" name="actual_result" class="form-control" rows="3" <?= ($testing_result === 'fail') ? '' : 'disabled' ?>><?= htmlspecialchars($row['actual_result'] ?? '') ?></textarea>
											</div>
										</div>
										
                                       
                                        <div class="submit-btn-container">
                                            <button type="submit" class="btn btn-primary w-100">
                                                Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php }
                        echo '</div>';
                    } else {
                        logUserAction(
                            $_SESSION['emp_id'] ?? null,
                            $_SESSION['user'],
                            'testcase_fetch_empty',
                            "No test cases found for product: $selected_product, version: $selected_version",
                            $_SERVER['REQUEST_URI'],
                            $_SERVER['REQUEST_METHOD'],
                            null,
                            200,
                            null,
                            $_SERVER['REMOTE_ADDR'],
                            $_SERVER['HTTP_USER_AGENT']
                        );
                       
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
           
            // Define emoji sets
            const passEmojis = ['😊', '👍', '🎉', '✨', '👏', '💯', '🥳', '✅'];
            const failEmojis = ['😞', '👎', '💔', '😕', '😟', '🙁', '😔', '❌'];
           
            // Function to create emoji animation
            function createEmojiAnimation(container, emojis, type) {
                const emoji = emojis[Math.floor(Math.random() * emojis.length)];
                const animation = $('<div class="emoji-animation"></div>').text(emoji).addClass(type + '-emojis');
            
                // Position relative to container
                const containerPos = container.offset();
                const left = Math.random() * container.width();
                const top = container.height() / 2;
            
                animation.css({
                    left: containerPos.left + left + 'px',
                    top: containerPos.top + top + 'px'
                });
            
                $('body').append(animation);
            
                // Remove after animation completes
                setTimeout(() => {
                    animation.remove();
                }, 3000);
            }
           
            // Set device info in all hidden fields
            $('#global_device_name, #global_android_version').on('change', function() {
                const deviceName = $('#global_device_name').val();
                const androidVersion = $('#global_android_version').val();
               
                $('[id^="device_name_"]').val(deviceName);
                $('[id^="android_version_"]').val(androidVersion);
            });
           
            // Trigger initial change to set values if they exist
            if ($('#global_device_name').val() || $('#global_android_version').val()) {
                $('#global_device_name, #global_android_version').trigger('change');
            }
           
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
                       
                        // Log APK version fetch
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'apk_version_fetch',
                                description: 'Fetched APK versions',
                                product: folder,
                                version_count: data.length
                            },
                            dataType: 'json'
                        });
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        // Log APK version fetch error
                        $.ajax({
                            url: 'log_api.php',
                            type: 'POST',
                            data: {
                                action: 'log_client_action',
                                action_type: 'apk_version_fetch_error',
                                description: 'Failed to fetch APK versions',
                                product: folder,
                                error: textStatus
                            },
                            dataType: 'json'
                        });
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
                   
                    // Log APK download initiation
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'apk_download_initiated',
                            description: 'Initiated APK download',
                            product: folder,
                            version: version,
                            filename: filename
                        },
                        dataType: 'json'
                    });
                   
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
               
                if (result === 'fail') {
                    bugDetails.slideDown();
                    $('#actual_result_' + testcaseId).prop('required', true);
                   
                    bugDetails.removeClass('d-none');
                    bugDetails.addClass('bug-details-fail');
                   
                    // Enable input fields
                    bugType.prop('disabled', false);
                    actualResult.prop('disabled', false);
                    fileAttachment.prop('disabled', false);
                   
                    // Clear existing values if not already set
                    if (!bugType.val()) {
                        bugType.val('');
                    }
                    if (!actualResult.val()) {
                        actualResult.val('');
                    }
                    if (!fileAttachment.val()) {
                        fileAttachment.val('');
                    }
                   
                    // Log test result selection (fail)
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'test_result_selected',
                            description: 'Selected test result: Fail',
                            testcase_id: testcaseId
                        },
                        dataType: 'json'
                    });
                } else {
                    bugDetails.slideUp();
                    $('#actual_result_' + testcaseId).prop('required', false);
                   
                    bugDetails.addClass('d-none');
                    bugDetails.removeClass('bug-details-fail');
                   
                    // Disable and clear input fields
                    bugType.prop('disabled', true).val('');
                    actualResult.prop('disabled', true).val('');
                    fileAttachment.prop('disabled', true).val('');
                   
                    // Log test result selection (pass)
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'test_result_selected',
                            description: 'Selected test result: Pass',
                            testcase_id: testcaseId
                        },
                        dataType: 'json'
                    });
                }
            });
           
            // File attachment change handler
            $(document).on('change', 'input[type="file"]', function() {
                const testcaseId = $(this).closest('.test-form').data('id');
                const file = this.files[0];
               
                if (file) {
                    const fileInfo = $(this).siblings('.file-info');
                    const progressBar = $(this).siblings('.upload-progress').find('.progress-bar');
                   
                    // Show file info
                    fileInfo.text(`Selected: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`);
                   
                    // Show progress bar
                    $(this).siblings('.upload-progress').show();
                   
                    // Simulate progress (in a real app, you'd use AJAX with progress events)
                    let progress = 0;
                    const progressInterval = setInterval(() => {
                        progress += 5;
                        progressBar.css('width', progress + '%');
                       
                        if (progress >= 100) {
                            clearInterval(progressInterval);
                        }
                    }, 100);
                   
                    // Log file selection
                    $.ajax({
                        url: 'log_api.php',
                        type: 'POST',
                        data: {
                            action: 'log_client_action',
                            action_type: 'attachment_selected',
                            description: 'Selected file attachment',
                            testcase_id: testcaseId,
                            file_name: file.name,
                            file_size: file.size
                        },
                        dataType: 'json'
                    });
                }
            });
           
			

// Function to validate device info (only for submission)
function validateDeviceInfo() {
    const deviceName = $('#global_device_name').val().trim();
    const androidVersion = $('#global_android_version').val().trim();
    
    // Reset validation
    $('#global_device_name, #global_android_version').removeClass('is-invalid');
    $('.device-info-feedback').remove();
    
    if (!deviceName) {
        $('#global_device_name').addClass('is-invalid')
            .after('<div class="invalid-feedback device-info-feedback">Device name is required</div>');
        return false;
    }
    
    if (!androidVersion) {
        $('#global_android_version').addClass('is-invalid')
            .after('<div class="invalid-feedback device-info-feedback">Android version is required</div>');
        return false;
    }
    
    return true;
}

// Update form submission handler
$('.test-form').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const testcaseId = form.data('id');
    const card = $('#card-' + testcaseId);
    const formData = new FormData(form[0]);
    const result = form.find('input[name="testing_result"]:checked').val();
    
    // First validate device info
    if (!validateDeviceInfo()) {
        // Scroll to first invalid field
        $('html, body').animate({
            scrollTop: $('.is-invalid').first().offset().top - 100
        }, 500);
        return;
    }
    
    // Validate form for fail case
    if (result === 'fail') {
        let hasError = false;
        
        // Reset validation
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        
        // Validate bug type
        const bugType = form.find('select[name="bug_type"]').val();
        if (!bugType) {
            form.find('select[name="bug_type"]').addClass('is-invalid')
                .after('<div class="invalid-feedback">Please select a bug type</div>');
            hasError = true;
        }
        
        // Validate actual result
        const actualResult = form.find('textarea[name="actual_result"]').val().trim();
        if (!actualResult) {
            form.find('textarea[name="actual_result"]').addClass('is-invalid')
                .after('<div class="invalid-feedback">Please describe the actual result</div>');
            hasError = true;
        }
        
        if (hasError) {
            // Scroll to first invalid field
            $('html, body').animate({
                scrollTop: form.find('.is-invalid').first().offset().top - 100
            }, 500);
            return;
        }
    }
    
    // Show loading spinner
    $('.spinner-overlay').addClass('show');
    
    // Submit the form
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('.spinner-overlay').removeClass('show');
            
            if (response && response.status === 'success') {
                // Show appropriate emoji animation based on result
                const emojis = result === 'pass' ? passEmojis : failEmojis;
                const emojiType = result === 'pass' ? 'pass' : 'fail';
                
                // Create multiple emoji animations
                for (let i = 0; i < 8; i++) {
                    setTimeout(() => {
                        createEmojiAnimation(card, emojis, emojiType);
                    }, i * 200);
                }
                // If result was fail, collapse the bug details section
				if (result === 'fail') {
					const bugDetails = $('#bug-details-' + testcaseId);
					bugDetails.slideUp();
					bugDetails.addClass('d-none');
					bugDetails.removeClass('bug-details-fail');
					
					// Also reset the radio buttons to unselected state
					form.find('input[name="testing_result"]').prop('checked', false);
					
					// Disable and clear input fields
					form.find('select[name="bug_type"]').prop('disabled', true).val('');
					form.find('textarea[name="actual_result"]').prop('disabled', true).val('');
					form.find('input[name="file_attachment"]').prop('disabled', true).val('');
				}
                // Show success message
                showSubmissionMessage('Test case submitted successfully!', 'success');
                
                // Log successful submission
                $.ajax({
                    url: 'log_api.php',
                    type: 'POST',
                    data: {
                        action: 'log_client_action',
                        action_type: 'testcase_submit_success',
                        description: 'Successfully submitted test case results',
                        testcase_id: testcaseId,
                        result: result
                    },
                    dataType: 'json'
                });
            } else {
                const errorMsg = response && response.message
                    ? response.message
                    : 'Unknown error occurred';
                
                // Show error message
                showSubmissionMessage('Error: ' + errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
            $('.spinner-overlay').removeClass('show');
            showSubmissionMessage('An error occurred. Please try again.', 'error');
        }
    });
});

// Filter form should not validate device info
$('#filter-form').on('submit', function(e) {
    const product = $('#product_name').val();
    const version = $('#version').val();
    
    // Only validate if both or neither are selected
    if ((product && !version) || (!product && version)) {
        e.preventDefault();
        showSubmissionMessage('Please select both Product and Version to filter', 'error');
        return false;
    }
    
    return true;
});
           
            // Helper function to show submission messages
            function showSubmissionMessage(message, type) {
                const messageDiv = $('#submission-message');
                messageDiv
                    .removeClass('success error')
                    .addClass(type)
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
                        description: 'Session timeout warning shown on test case page'
                    },
                    dataType: 'json'
                });
            }, sessionTimeout - popupTime);

            // Redirect to logout after timeout
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, sessionTimeout);
        });
       
        // Function to toggle admin links visibility
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'none' ? 'block' : 'none';
           
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
    </script>
</body>
</html>