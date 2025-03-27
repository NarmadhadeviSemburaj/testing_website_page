<?php
session_start();
// Set session timeout to 5 minutes (300 seconds)
$timeout = 300; // 5 minutes in seconds

// Include database configuration and logging functions
include 'db_config.php';
include 'log_api.php';

// Initialize logging
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    // Last request was more than 5 minutes ago
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'] ?? 'unknown',
        'session_timeout',
        "Session timed out due to inactivity",
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
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user'])) {
    logUserAction(
        null,
        'unknown',
        'unauthorized_access',
        "Attempted access without authentication",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        403,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    header("Location: login.php");
    exit();
}

// Log page access
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'],
    'page_access',
    "Accessed TCM page",
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
        'database_error',
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

// Fetch distinct products
$sql_products = "SELECT DISTINCT Product_name FROM testcase";
$result_products = $conn->query($sql_products);
if (!$result_products) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'database_error',
        "Product query failed: " . $conn->error,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        $sql_products,
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
}

// Fetch distinct versions
$sql_versions = "SELECT DISTINCT Version FROM testcase";
$result_versions = $conn->query($sql_versions);
if (!$result_versions) {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'database_error',
        "Version query failed: " . $conn->error,
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        $sql_versions,
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
}

// Preserve filter criteria after form submission
$selected_product = $_POST['product_name'] ?? '';
$selected_version = $_POST['version'] ?? '';

// Log filter application if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'filter_applied',
        "Applied filters for test cases",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        json_encode(['product' => $selected_product, 'version' => $selected_version]),
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Case Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            background-color: #fff;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        
        .card-details {
            margin-bottom: 10px;
        }
        
        .card-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        
        .card-details i {
            margin-right: 8px;
            color: #007bff;
        }
        
        .update-btn {
            text-align: right;
        }
        
        .update-btn .btn-warning {
            background-color: green;
            border-color: green;
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
        
        .filter-row .form-group label {
            font-size: 14px;
        }
		.progress {
				height: 25px;
				border-radius: 5px;
			}
			.progress-bar {
				font-weight: bold;
			}
			#uploadStatusMessage {
				font-weight: bold;
				margin: 10px 0;
			}
			#uploadDetails {
				font-size: 0.9em;
			}
        
        .filter-row .form-select {
            font-size: 14px;
            padding: 6px 12px;
        }
        
        .filter-row .btn {
            flex: 0 0 auto;
            font-size: 14px;
            padding: 6px 12px;
        }
        
        .product-checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .product-checkbox-grid .form-check {
            flex: 1 1 calc(50% - 10px);
        }
        
        .add-testcase-btn, .upload-excel-btn {
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
        
        .upload-excel-btn {
            right: 100px;
            background-color: #28a745;
        }
        
        .add-testcase-btn:hover {
            background-color: #0056b3;
        }
        
        .upload-excel-btn:hover {
            background-color: #218838;
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
                            <a href="view_logs.php" class="<?php echo ($current_page == 'view_logs.php') ? 'active' : ''; ?>">
                                <i class="fas fa-clipboard-list"></i> View Logs
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <h3>TCM</h3>
            <!-- + Icon for Adding Test Case -->
            <button class="btn btn-primary add-testcase-btn" data-bs-toggle="modal" data-bs-target="#testCaseModal" onclick="logClientAction('open_add_testcase_modal', 'Opened modal to add new test case')">
                <i class="fas fa-plus"></i>
            </button>
            <button class="btn btn-success upload-excel-btn" onclick="document.getElementById('excel_file').click(); logClientAction('click_upload_excel', 'Clicked to upload Excel file')">
                <i class="fas fa-upload"></i>
            </button>
            <input type="file" id="excel_file" name="excel_file" accept=".xls, .xlsx" style="display: none;">

            <!-- Filters for Product and Version -->
            <form method="POST" class="mb-4" onsubmit="logClientAction('apply_filters', 'Applying test case filters')">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="product_name" class="form-label">Select Product:</label>
                        <select name="product_name" class="form-select">
                            <option value="">-- Select Product --</option>
                            <?php while ($row = $result_products->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Product_name']); ?>" <?= ($selected_product == $row['Product_name']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Product_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="version" class="form-label">Select Version:</label>
                        <select name="version" class="form-select">
                            <option value="">-- Select Version --</option>
                            <?php while ($row = $result_versions->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Version']); ?>" <?= ($selected_version == $row['Version']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Version']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <!-- Test Cases Display -->
            <div class="row">
                <?php
                // Fetch test cases based on filters
                $sql = "SELECT * FROM testcase WHERE 1=1";
                if (!empty($selected_product)) {
                    $sql .= " AND Product_name = '" . $conn->real_escape_string($selected_product) . "'";
                }
                if (!empty($selected_version)) {
                    $sql .= " AND Version = '" . $conn->real_escape_string($selected_version) . "'";
                }
                
                logUserAction(
                    $_SESSION['emp_id'] ?? null,
                    $_SESSION['user'],
                    'database_query',
                    "Executed test case query",
                    $_SERVER['REQUEST_URI'],
                    $_SERVER['REQUEST_METHOD'],
                    $sql,
                    200,
                    null,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                );
                
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    logUserAction(
                        $_SESSION['emp_id'] ?? null,
                        $_SESSION['user'],
                        'test_cases_displayed',
                        "Displayed " . $result->num_rows . " test cases",
                        $_SERVER['REQUEST_URI'],
                        $_SERVER['REQUEST_METHOD'],
                        null,
                        200,
                        null,
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    );
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="col-md-4">
                                <div class="card">
                                    <div class="card-title">
                                        <i class="fas fa-folder"></i> ' . htmlspecialchars($row['Module_name']) . '
                                    </div>
                                    <div class="card-details">
                                        <p><i class="fas fa-box"></i> <strong>Product:</strong> ' . htmlspecialchars($row['Product_name']) . '</p>
                                        <p><i class="fas fa-code-branch"></i> <strong>Version:</strong> ' . htmlspecialchars($row['Version']) . '</p>
                                        <p><i class="fas fa-align-left"></i> <strong>Description:</strong> ' . htmlspecialchars($row['description']) . '</p>
                                        <p><i class="fas fa-check-circle"></i> <strong>Preconditions:</strong> ' . htmlspecialchars($row['preconditions'] ?? 'N/A') . '</p>
                                        <p><i class="fas fa-list-ol"></i> <strong>Test Steps:</strong> ' . htmlspecialchars($row['test_steps']) . '</p>
                                        <p><i class="fas fa-clipboard-check"></i> <strong>Expected Results:</strong> ' . htmlspecialchars($row['expected_results']) . '</p>
                                    </div>
                                    <div class="update-btn">
                                        <button class="btn btn-warning btn-sm edit-btn" data-id="' . htmlspecialchars($row['id']) . '" data-bs-toggle="modal" data-bs-target="#testCaseModal" onclick="logClientAction(\'edit_testcase_attempt\', \'Attempted to edit test case ID ' . htmlspecialchars($row['id']) . '\')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="delete_testcase1.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-danger btn-sm" onclick="logClientAction(\'delete_testcase_attempt\', \'Attempted to delete test case ID ' . htmlspecialchars($row['id']) . '\'); return confirm(\'Are you sure?\');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                              </div>';
                    }
                } else {
                    logUserAction(
                        $_SESSION['emp_id'] ?? null,
                        $_SESSION['user'],
                        'test_cases_not_found',
                        "No test cases found with current filters",
                        $_SERVER['REQUEST_URI'],
                        $_SERVER['REQUEST_METHOD'],
                        null,
                        200,
                        null,
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    );
                    echo '<div class="col-12"><p class="text-center">No test cases found</p></div>';
                }
                ?>
            </div>

            <!-- Modal for Adding/Editing Test Case -->
            <div class="modal fade" id="testCaseModal" tabindex="-1" aria-labelledby="testCaseModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="testCaseModalLabel">Add/Edit Test Case</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="testCaseForm">
                                <input type="hidden" id="id" name="id" value="">
                                
                                <!-- Product Selection (Checklist) -->
                                <div class="mb-3">
                                    <label class="form-label">Select Products</label>
                                    <div class="product-checkbox-grid">
                                        <?php
                                        $uploadDir = "uploads/";
                                        $folders = array_filter(glob($uploadDir . '*'), 'is_dir');
                                        foreach ($folders as $folder) {
                                            $folderName = basename($folder);
                                            echo "<div class='form-check'>
                                                    <input class='form-check-input' type='checkbox' name='product_name[]' value='" . htmlspecialchars($folderName) . "' id='product_" . htmlspecialchars($folderName) . "'>
                                                    <label class='form-check-label' for='product_" . htmlspecialchars($folderName) . "'>" . htmlspecialchars($folderName) . "</label>
                                                  </div>";
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- Version Selection -->
                                <div class="mb-3">
                                    <label for="version" class="form-label">Version</label>
                                    <select class="form-control" id="version" name="version" required>
                                        <option value="">Select Version</option>
                                    </select>
                                </div>

                                <!-- Module Name -->
                                <div class="mb-3">
                                    <label for="module_name" class="form-label">Module Name</label>
                                    <input type="text" class="form-control" id="module_name" name="module_name" value="">
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description"></textarea>
                                </div>

                                <!-- Preconditions -->
                                <div class="mb-3">
                                    <label for="preconditions" class="form-label">Preconditions</label>
                                    <textarea class="form-control" id="preconditions" name="preconditions"></textarea>
                                </div>

                                <!-- Test Steps -->
                                <div class="mb-3">
                                    <label for="test_steps" class="form-label">Test Steps</label>
                                    <textarea class="form-control" id="test_steps" name="test_steps"></textarea>
                                </div>

                                <!-- Expected Results -->
                                <div class="mb-3">
                                    <label for="expected_results" class="form-label">Expected Results</label>
                                    <textarea class="form-control" id="expected_results" name="expected_results"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Test Case</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="modal fade" id="uploadProgressModal" tabindex="-1" aria-labelledby="uploadProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadProgressModalLabel">Uploading Excel File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" disabled></button>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="uploadStatusMessage" class="text-center">Preparing upload...</div>
                <div id="uploadDetails" class="small text-muted mt-2"></div>
            </div>
        </div>
    </div>
</div>


    <script>
        // JavaScript to handle edit button click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const testCaseId = this.getAttribute('data-id');
                console.log("Editing test case with ID:", testCaseId);
                
                if (!testCaseId) {
                    logClientAction('edit_testcase_error', 'Invalid or missing test case ID');
                    alert("Invalid or missing ID");
                    return;
                }

                // Log the edit action attempt
                logClientAction('edit_testcase_fetch', 'Fetching test case details for ID ' + testCaseId);

                // Fetch test case details using AJAX
                fetch(`fetch_testcase1.php?id=${encodeURIComponent(testCaseId)}`)
                    .then(response => {
                        if (!response.ok) {
                            logClientAction('edit_testcase_fetch_error', 'Failed to fetch test case details for ID ' + testCaseId);
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("Received data:", data);
                        if (data.status === "success") {
                            logClientAction('edit_testcase_fetch_success', 'Successfully fetched test case details for ID ' + testCaseId);
                            
                            // Populate the modal form with the fetched data
                            document.getElementById('id').value = data.data.id;
                            document.getElementById('module_name').value = data.data.Module_name;
                            document.getElementById('description').value = data.data.description;
                            document.getElementById('preconditions').value = data.data.preconditions || '';
                            document.getElementById('test_steps').value = data.data.test_steps;
                            document.getElementById('expected_results').value = data.data.expected_results;

                            // Uncheck all product checkboxes first
                            document.querySelectorAll('.product-checkbox-grid .form-check-input').forEach(checkbox => {
                                checkbox.checked = false;
                            });

                            // Check the product(s) associated with this test case
                            const productNames = Array.isArray(data.data.Product_name) 
                                ? data.data.Product_name 
                                : [data.data.Product_name];
                            
                            productNames.forEach(productName => {
                                const checkbox = document.querySelector(`.product-checkbox-grid .form-check-input[value="${productName}"]`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });

                            // Fetch versions for the selected products
                            fetchVersionsForSelectedProducts(data.data.Version);
                        } else {
                            logClientAction('edit_testcase_fetch_failed', 'Failed to fetch test case: ' + (data.message || "Unknown error"));
                            alert(data.message || "Error fetching test case");
                        }
                    })
                    .catch(error => {
                        logClientAction('edit_testcase_fetch_exception', 'Exception while fetching test case: ' + error.message);
                        console.error("Error fetching test case:", error);
                        alert("An error occurred while fetching the test case.");
                    });
            });
        });

        // Function to fetch versions for selected products
        function fetchVersionsForSelectedProducts(selectedVersion = null) {
            const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox-grid .form-check-input:checked'))
                .map(checkbox => checkbox.value);

            if (selectedProducts.length > 0) {
                fetch(`fetch_versions.php?folders=${selectedProducts.join(',')}`)
                    .then(response => response.json())
                    .then(data => {
                        const versionSelect = document.getElementById('version');
                        if (data.status === "success") {
                            versionSelect.innerHTML = '<option value="">Select Version</option>';
                            data.data.forEach(version => {
                                const option = document.createElement('option');
                                option.value = version;
                                option.textContent = version;
                                if (selectedVersion && version === selectedVersion) {
                                    option.selected = true;
                                }
                                versionSelect.appendChild(option);
                            });
                        } else {
                            versionSelect.innerHTML = '<option value="">No versions found</option>';
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching versions:", error);
                    });
            } else {
                const versionSelect = document.getElementById('version');
                versionSelect.innerHTML = '<option value="">Select Version</option>';
            }
        }

        // Handle Form Submission
        document.getElementById('testCaseForm').addEventListener('submit', function(event) {
            event.preventDefault();

            // Gather form data
            const formData = {
                id: document.getElementById('id').value,
                product_name: Array.from(document.querySelectorAll('.product-checkbox-grid .form-check-input:checked'))
                    .map(checkbox => checkbox.value),
                version: document.getElementById('version').value,
                module_name: document.getElementById('module_name').value,
                description: document.getElementById('description').value,
                preconditions: document.getElementById('preconditions').value,
                test_steps: document.getElementById('test_steps').value,
                expected_results: document.getElementById('expected_results').value
            };

            console.log("Submitting form data:", formData);
            
            // Log the form submission attempt
            const action = formData.id ? 'update_testcase_attempt' : 'create_testcase_attempt';
            logClientAction(action, 'Submitting test case form for ' + (formData.id ? 'ID ' + formData.id : 'new test case'));

            // Send data to the API
            fetch('submit_testcase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    logClientAction(action + '_error', 'Network error while submitting test case');
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Response data:", data);
                if (data.status === "success") {
                    logClientAction(action + '_success', 'Successfully ' + (formData.id ? 'updated' : 'created') + ' test case');
                    alert(data.message);
                    location.reload(); // Refresh to show new data
                } else {
                    logClientAction(action + '_failed', 'Failed to submit test case: ' + (data.message || "Unknown error"));
                    alert(data.message || "Error saving test case");
                }
            })
            .catch(error => {
                logClientAction(action + '_exception', 'Exception while submitting test case: ' + error.message);
                console.error("Error:", error);
                alert("An error occurred while saving the test case.");
            });
        });

        // Fetch versions when products are selected/deselected
        document.querySelectorAll('.product-checkbox-grid .form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                fetchVersionsForSelectedProducts();
            });
        });

        // Function to toggle admin links visibility
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'none' ? 'block' : 'none';
        }
         document.getElementById("excel_file").addEventListener("change", function() {
        let fileInput = this;
        if (fileInput.files.length === 0) {
            logClientAction('excel_upload_canceled', 'No file selected for upload');
            return;
        }

        const file = fileInput.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB max file size
        
        // Validate file size
        if (file.size > maxSize) {
            logClientAction('excel_upload_size_error', 'File too large: ' + file.name);
            alert('File size exceeds 10MB limit');
            return;
        }

        // Show progress modal
        const progressModal = new bootstrap.Modal(document.getElementById('uploadProgressModal'));
        const progressBar = document.getElementById('uploadProgressBar');
        const statusMessage = document.getElementById('uploadStatusMessage');
        const uploadDetails = document.getElementById('uploadDetails');
        
        progressModal.show();
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        statusMessage.textContent = 'Preparing upload...';
        uploadDetails.textContent = `File: ${file.name} (${formatFileSize(file.size)})`;
        
        // Log file upload attempt
        logClientAction('excel_upload_attempt', 'Attempting to upload Excel file: ' + file.name);

        let formData = new FormData();
        formData.append("excel_file", file);

        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressBar.textContent = percentComplete + '%';
                statusMessage.textContent = `Uploading... (${percentComplete}%)`;
                
                // Update details with transfer info
                uploadDetails.textContent = `File: ${file.name} (${formatFileSize(e.loaded)} of ${formatFileSize(e.total)})`;
            }
        });

        xhr.addEventListener('load', function() {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.status === "success") {
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-success');
                    statusMessage.textContent = 'Upload complete! Processing data...';
                    logClientAction('excel_upload_success', 'Successfully uploaded Excel file: ' + file.name);
                    
                    // Simulate processing delay (actual processing happens server-side)
                    setTimeout(() => {
                        progressModal.hide();
                        alert(data.message);
                        location.reload();
                    }, 1000);
                } else {
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-danger');
                    statusMessage.textContent = 'Upload failed';
                    logClientAction('excel_upload_failed', 'Failed to upload Excel file: ' + (data.message || "Unknown error"));
                    setTimeout(() => {
                        progressModal.hide();
                        alert(data.message || "Error processing file");
                    }, 1000);
                }
            } catch (e) {
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-danger');
                statusMessage.textContent = 'Error processing response';
                logClientAction('excel_upload_parse_error', 'Error parsing server response: ' + e.message);
                setTimeout(() => {
                    progressModal.hide();
                    alert("Error processing server response");
                }, 1000);
            }
        });

        xhr.addEventListener('error', function() {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-danger');
            statusMessage.textContent = 'Upload failed';
            logClientAction('excel_upload_error', 'Network error during upload');
            setTimeout(() => {
                progressModal.hide();
                alert("Network error during upload");
            }, 1000);
        });

        xhr.addEventListener('abort', function() {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-warning');
            statusMessage.textContent = 'Upload cancelled';
            logClientAction('excel_upload_cancelled', 'Upload cancelled by user');
            setTimeout(() => {
                progressModal.hide();
            }, 1000);
        });

        xhr.open("POST", "upload_excel.php", true);
        xhr.send(formData);
        
        // Make modal non-closable during upload
        const modalElement = document.getElementById('uploadProgressModal');
        modalElement.addEventListener('hide.bs.modal', function (event) {
            if (xhr.readyState !== 4) { // If upload not complete
                event.preventDefault();
            }
        });
    });

    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
        
        
        // Clear form when modal is opened for adding new test case
        document.getElementById('testCaseModal').addEventListener('show.bs.modal', function (event) {
            // If the modal was triggered by an edit button, don't clear the form
            if (!event.relatedTarget || !event.relatedTarget.classList.contains('edit-btn')) {
                document.getElementById('testCaseForm').reset();
                document.getElementById('id').value = '';
                document.getElementById('version').innerHTML = '<option value="">Select Version</option>';
                
                // Uncheck all product checkboxes
                document.querySelectorAll('.product-checkbox-grid .form-check-input').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Log modal open for new test case
                logClientAction('new_testcase_modal_opened', 'Opened modal to add new test case');
            }
        });

        // Enhanced client-side logging function
        function logClientAction(actionType, description) {
            // Send to server-side logging
            fetch('log_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'log_client_action',
                    emp_id: <?php echo json_encode($_SESSION['emp_id'] ?? null); ?>,
                    username: <?php echo json_encode($_SESSION['user'] ?? 'unknown'); ?>,
                    action_type: actionType,
                    description: description,
                    page_url: window.location.href,
                    ip_address: '', // Will be captured server-side
                    user_agent: navigator.userAgent
                })
            }).catch(e => console.error('Error logging client action:', e));
            
            // Also log to console for debugging
            console.log(`[CLIENT ACTION] ${actionType}: ${description}`);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
// Log page execution completion
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'],
    'page_execution_complete',
    "Completed execution of TCM page",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);
$conn->close(); 
?>