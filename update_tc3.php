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

// Assume logged-in user's name is stored in the session
$logged_in_user = $_SESSION['emp_name'] ?? 'Unknown';

// Fetch distinct products
$sql_products = "SELECT DISTINCT Product_name FROM testcase";
$result_products = $conn->query($sql_products);

// Fetch distinct versions
$sql_versions = "SELECT DISTINCT Version FROM testcase";
$result_versions = $conn->query($sql_versions);

// Preserve filter criteria after form submission
$selected_product = $_POST['product_name'] ?? '';
$selected_version = $_POST['version'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Test Case</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Your existing CSS styles */
		* Your existing CSS styles */
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
        .card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            background-color: #fff;
            height: 100%; /* Make all cards the same height */
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .card-title {
            font-size: 16px; /* Slightly smaller card title */
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        .card-details {
            margin-bottom: 10px;
            flex-grow: 1; /* Allow this section to grow and fill space */
        }
        .card-details p {
            margin: 5px 0;
            font-size: 14px; /* Card details font size */
            color: #555;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-details i {
            margin-right: 8px;
            color: #007bff;
            width: 14px; /* Fixed width for icons */
            text-align: center;
        }
        .update-btn {
            text-align: right;
            margin-top: auto; /* Push to bottom of card */
        }
        .tick-icon {
            color: red;
            margin-left: 10px;
        }
        .hidden-form {
            display: none;
            margin-top: 10px;
        }
        .hidden-form.active {
            display: block;
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
            font-size: 14px; /* Smaller label font size */
        }
        .filter-row .form-select {
            font-size: 14px; /* Smaller dropdown font size */
            padding: 6px 6px; /* Smaller padding for dropdowns */
        }
        .filter-row .btn {
            flex: 0 0 auto;
            font-size: 14px; /* Smaller button font size */
            padding: 6px 12px; /* Smaller padding for button */
        }
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
        .download-apk-btn {
            position: absolute;
            top: 40px;                 
            right: 40px;
        }
        .sidebar a i {
            margin-right: 10px; /* Adjust spacing */
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <!-- Download APK Button -->
            <a href="fetch1.php" class="btn btn-primary download-apk-btn" title="Download APK">
                <i class="fas fa-download"></i>
            </a>

            <!-- Rest of the content -->
            <h3>Testing</h3>
            <form method="POST" class="mb-4">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="product_name" class="form-label">Select Product:</label>
                        <select name="product_name" required class="form-select">
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
                        <select name="version" required class="form-select">
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

            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'], $_POST['version'])) {
                $product_name = $_POST['product_name'];
                $version = $_POST['version'];

                $sql = "SELECT * FROM testcase WHERE Product_name = ? AND Version = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $product_name, $version);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo "<h5 class='mt-4'>Test Cases for $product_name - $version </h5>";
                    echo '<div class="row row-cols-1 row-cols-md-3 g-4">'; // Using Bootstrap grid with gutters
                    while ($row = $result->fetch_assoc()) { 
                        $testcase_id = $row['id']; // Correct column name
                        $is_updated = !empty($row['tested_by_name']); // Check if the test case has been updated
                    ?>
                        <div class="col">
                            <div class="card">
                                <div class="card-title">
                                    <i class="fas fa-folder"></i> <?= htmlspecialchars($row['Module_name']); ?>
                                </div>
                                <div class="card-details">
                                    <p><i class="fas fa-align-left"></i> <strong>Description:</strong> <?= htmlspecialchars($row['description']); ?></p>
                                    <p><i class="fas fa-list-ol"></i> <strong>Test Steps:</strong> <?= htmlspecialchars($row['test_steps']); ?></p>
                                    <p><i class="fas fa-check-circle"></i> <strong>Preconditions:</strong> <?= htmlspecialchars($row['preconditions']); ?></p>
                                    <p><i class="fas fa-clipboard-check"></i> <strong>Expected Result:</strong> <?= htmlspecialchars($row['expected_results']); ?></p>
                                </div>
                                <div class="update-btn">
                                    <button class="btn btn-success btn-sm" onclick="toggleForm(<?= $testcase_id; ?>)" title="Update">
                                        <i class="fas fa-edit"></i> 
                                    </button>
                                    <?php if ($is_updated): ?>
                                        <i class="fas fa-check tick-icon" title="Test Case Updated"></i>
                                    <?php endif; ?>
                                </div>
                                <!-- Update Form Inside the Card -->
                                <div id="form-<?= $testcase_id; ?>" class="hidden-form">
                                    <form id="updateForm-<?= $testcase_id; ?>">
                                        <input type="hidden" name="id" value="<?= $testcase_id; ?>">
                                        <input type="hidden" name="tested_by_name" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                                        <input type="hidden" name="tested_at" value="<?= date('Y-m-d H:i:s'); ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="actual_result">Actual Result:</label>
                                                <textarea name="actual_result" class="form-control" required><?= htmlspecialchars($row['actual_result']); ?></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="device_name">Device Name:</label>
                                                <input type="text" name="device_name" class="form-control" value="<?= htmlspecialchars($row['device_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="android_version">Android Version:</label>
                                                <input type="text" name="android_version" class="form-control" value="<?= htmlspecialchars($row['android_version']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="file_attachment">Attach Screenshot:</label>
                                                <input type="file" name="file_attachment" class="form-control" id="file-<?= $testcase_id; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="testing_result">Testing Result:</label>
                                                <select name="testing_result" class="form-select">
                                                    <option value="Pass" <?= ($row['testing_result'] == 'Pass') ? 'selected' : ''; ?>>Pass</option>
                                                    <option value="Fail" <?= ($row['testing_result'] == 'Fail') ? 'selected' : ''; ?>>Fail</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="bug_type">Bug Type:</label>
                                                <select name="bug_type" class="form-select">
                                                    <option value="Critical" <?= ($row['bug_type'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                                    <option value="High" <?= ($row['bug_type'] == 'High') ? 'selected' : ''; ?>>High</option>
                                                    <option value="Low" <?= ($row['bug_type'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                                    <option value="Nil" <?= ($row['bug_type'] == 'Nil') ? 'selected' : ''; ?>>Nil</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12 mt-3">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
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
        // Function to toggle the visibility of admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            if (adminLinks.style.display === 'block') {
                adminLinks.style.display = 'none';
            } else {
                adminLinks.style.display = 'block';
            }
        }

        // Function to toggle the visibility of the update form
        function toggleForm(testcaseId) {
            const form = document.getElementById(`form-${testcaseId}`);
            form.classList.toggle('active');
        }

        // Handle form submission via AJAX
        $(document).ready(function() {
            $('[id^=updateForm-]').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const testcaseId = form.find('input[name="id"]').val();

                // Convert file to Base64
                const fileInput = document.getElementById(`file-${testcaseId}`);
                const file = fileInput.files[0];
                const reader = new FileReader();

                if (file) {
                    reader.readAsDataURL(file);
                    reader.onload = function() {
                        const base64File = reader.result.split(',')[1]; // Remove the data URL prefix
                        submitForm(form, base64File);
                    };
                } else {
                    submitForm(form, null);
                }
            });
        });

        // Submit form data to the API
        function submitForm(form, base64File) {
            const formData = {
                id: form.find('input[name="id"]').val(),
                tested_by_name: form.find('input[name="tested_by_name"]').val(),
                tested_at: form.find('input[name="tested_at"]').val(),
                actual_result: form.find('textarea[name="actual_result"]').val(),
                device_name: form.find('input[name="device_name"]').val(),
                android_version: form.find('input[name="android_version"]').val(),
                testing_result: form.find('select[name="testing_result"]').val(),
                bug_type: form.find('select[name="bug_type"]').val(),
                file_attachment: base64File || ''
            };

            $.ajax({
                url: 'update_testcases.php', // Ensure this points to the correct API endpoint
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert(response.message || 'Failed to update test case');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Failed to update test case');
                }
            });
        }
    </script>
</body>
</html>


