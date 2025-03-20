<?php
session_start();
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
    
    // Store device name and Android version in session
    if (isset($_POST['device_name'])) {
        $_SESSION['device_name'] = $_POST['device_name'];
    }
    if (isset($_POST['android_version'])) {
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
        /* Your existing CSS styles */html, body {
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
        .download-apk-btn {
            position: absolute;
            top: 60px; /* Adjusted position */
            right: 40px;
        }
        .sidebar a i {
            margin-right: 10px; /* Adjust spacing */
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
            transition: all 0.3s ease;
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
        .admin-links {
            display: none; /* Initially hidden */
            transition: all 0.3s ease; /* Smooth transition */
        }

        .admin-links.show {
            display: block; /* Show the dropdown */
        }
		.view-more-btn {
			color: #007bff;
			cursor: pointer;
			font-size: 14px;
			margin-top: 5px;
			text-align: right;
		}
		.view-more-btn:hover {
			text-decoration: underline;
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
        /* New styles for highlighting active card */
        .card.active-card {
            border: 2px solid #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        /* Loading spinner */
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
    </style>
</head>
<body>
    <div class="spinner-overlay">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
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

                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
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
            <!-- Download APK Button -->
            <a href="fetch1.php" class="btn btn-primary download-apk-btn" title="Download APK">
                <i class="fas fa-download"></i>
            </a>

            <!-- Notification area for submission feedback -->
            <div id="submission-message" class="submission-message"></div>

            <!-- Rest of the content -->
            <h3>Testing</h3>
            <form id="filter-form" method="POST" class="mb-4">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="product_name" class="form-label">Select Product:</label>
                        <select name="product_name" id="product_name" required class="form-select">
                            <option value="">-- Select Product --</option>
                            <?php 
                            $result_products->data_seek(0); // Reset the result pointer
                            while ($row = $result_products->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Product_name']); ?>" <?= ($selected_product == $row['Product_name']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Product_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="version" class="form-label">Select Version:</label>
                        <select name="version" id="version" required class="form-select">
                            <option value="">-- Select Version --</option>
                            <?php 
                            $result_versions->data_seek(0); // Reset the result pointer
                            while ($row = $result_versions->fetch_assoc()) { ?>
                                <option value="<?= htmlspecialchars($row['Version']); ?>" <?= ($selected_version == $row['Version']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($row['Version']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>

                <!-- Device Name and Android Version -->
                <div class="filter-row mt-3">
                    <div class="form-group">
                        <label for="device_name" class="form-label">Device Name:</label>
                        <input type="text" name="device_name" id="device_name" class="form-control" value="<?= htmlspecialchars($device_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="android_version" class="form-label">Android Version:</label>
                        <input type="text" name="android_version" id="android_version" class="form-control" value="<?= htmlspecialchars($android_version); ?>" required>
                    </div>
                    <button type="submit" name="reset" class="btn btn-danger">Reset</button>
                </div>
            </form>

            <div id="test-cases-container">
                <?php
                if (!empty($selected_product) && !empty($selected_version)) {
                    $sql = "SELECT * FROM testcase WHERE Product_name = ? AND Version = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $selected_product, $selected_version);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        echo "<h2 class='mt-4'>Test Cases for $selected_product - $selected_version</h2>";
                        echo '<div class="row row-cols-1 row-cols-md-3 g-4" id="test-cards">';
                        while ($row = $result->fetch_assoc()) { 
                            $testcase_id = $row['id'];
                            $is_updated = !empty($row['tested_by_name']);
                        ?>
                            <div class="col test-case-col" data-id="<?= $testcase_id ?>">
                                <div class="card" id="card-<?= $testcase_id ?>">
                                    <div class="card-title">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($row['Module_name']); ?>
                                    </div>
                                    <div class="card-details">
										<p><i class="fas fa-align-left"></i> <strong>Description:</strong> <?= htmlspecialchars($row['description']); ?></p>
										<p><i class="fas fa-list-ol"></i> <strong>Test Steps:</strong> <?= htmlspecialchars($row['test_steps']); ?></p>
										<p><i class="fas fa-check-circle"></i> <strong>Preconditions:</strong> <?= htmlspecialchars($row['preconditions']); ?></p>
										<p><i class="fas fa-clipboard-check"></i> <strong>Expected Result:</strong> <?= htmlspecialchars($row['expected_results']); ?></p>
										<div class="view-more-btn">View More</div>
									</div>
                                    <div class="update-btn">
                                        <button class="btn btn-success btn-sm toggle-form-btn" data-id="<?= $testcase_id; ?>" title="Update">
                                            <i class="fas fa-edit"></i> 
                                        </button>
                                        <?php if ($is_updated): ?>
                                            <i class="fas fa-check tick-icon" title="Test Case Updated"></i>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Update Form Inside the Card -->
                                    <div id="form-<?= $testcase_id; ?>" class="hidden-form">
                                        <form class="ajax-form" data-id="<?= $testcase_id; ?>" action="update_testcases.php" method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="id" value="<?= $testcase_id; ?>">
                                            <input type="hidden" name="tested_by_name" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                                            <input type="hidden" name="tested_at" value="<?= date('Y-m-d H:i:s'); ?>">
                                            <input type="hidden" name="device_name" value="<?= htmlspecialchars($device_name); ?>">
                                            <input type="hidden" name="android_version" value="<?= htmlspecialchars($android_version); ?>">
                                            <input type="hidden" name="product_name" value="<?= htmlspecialchars($selected_product); ?>">
                                            <input type="hidden" name="version" value="<?= htmlspecialchars($selected_version); ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="actual_result_<?= $testcase_id; ?>">Actual Result:</label>
                                                    <textarea id="actual_result_<?= $testcase_id; ?>" name="actual_result" class="form-control" required><?= htmlspecialchars($row['actual_result']); ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="file_attachment_<?= $testcase_id; ?>">Attach Screenshot:</label>
                                                    <input type="file" id="file_attachment_<?= $testcase_id; ?>" name="file_attachment" class="form-control">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="testing_result_<?= $testcase_id; ?>">Testing Result:</label>
                                                    <select id="testing_result_<?= $testcase_id; ?>" name="testing_result" class="form-select">
                                                        <option value="Pass" <?= ($row['testing_result'] == 'Pass') ? 'selected' : ''; ?>>Pass</option>
                                                        <option value="Fail" <?= ($row['testing_result'] == 'Fail') ? 'selected' : ''; ?>>Fail</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="bug_type_<?= $testcase_id; ?>">Bug Type:</label>
                                                    <select id="bug_type_<?= $testcase_id; ?>" name="bug_type" class="form-select">
														<option value="Nil" <?= ($row['bug_type'] == 'Nil') ? 'selected' : ''; ?>>Nil</option>
														<option value="Low" <?= ($row['bug_type'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                                        <option value="High" <?= ($row['bug_type'] == 'High') ? 'selected' : ''; ?>>High</option>
														<option value="Critical" <?= ($row['bug_type'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                                                        
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
		$(document).ready(function() {
    // Toggle admin links
    function toggleAdminLinks() {
        $('.admin-links').toggleClass('show');
    }

    // Form toggle function
    function toggleForm(testcaseId) {
        const form = $('#form-' + testcaseId);
        $('.hidden-form').not(form).removeClass('active');
        form.toggleClass('active');

        // Highlight the active card
        $('.card').removeClass('active-card');
        if (form.hasClass('active')) {
            $('#card-' + testcaseId).addClass('active-card');
        }
    }

    // Attach click event to toggle buttons
    $(document).on('click', '.toggle-form-btn', function(e) {
        e.preventDefault();
        const testcaseId = $(this).data('id');
        toggleForm(testcaseId);
    });

    // AJAX form submission
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const testcaseId = form.data('id');
        const formData = new FormData(this);

        // Show loading spinner
        $('.spinner-overlay').addClass('show');

        $.ajax({
            url: 'update_testcases.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Hide loading spinner
                $('.spinner-overlay').removeClass('show');

                // Show success message
                const messageDiv = $('#submission-message');
                messageDiv.removeClass('error').addClass('success');
                messageDiv.html('Test case updated successfully!');
                messageDiv.fadeIn().delay(3000).fadeOut();

                // Add check mark if not already present
                const cardElement = $('#card-' + testcaseId);
                const updateBtn = cardElement.find('.update-btn');
                if (updateBtn.find('.tick-icon').length === 0) {
                    updateBtn.append('<i class="fas fa-check tick-icon" title="Test Case Updated"></i>');
                }

                // Hide the form after submission
                toggleForm(testcaseId);

                // If there's a next test case ID, scroll to it
                if (response.next_id) {
                    const nextCard = $('#card-' + response.next_id);
                    if (nextCard.length) {
                        $('html, body').animate({
                            scrollTop: nextCard.offset().top - 100
                        }, 500);
                    }
                }
            },
            error: function(xhr, status, error) {
                // Hide loading spinner
                $('.spinner-overlay').removeClass('show');

                // Show error message
                const messageDiv = $('#submission-message');
                messageDiv.removeClass('success').addClass('error');
                messageDiv.html('Error updating test case: ' + xhr.responseText);
                messageDiv.fadeIn().delay(3000).fadeOut();
            }
        });
    });

    // Reset filters
    $(document).on('click', 'button[name="reset"]', function(e) {
        e.preventDefault();
        $('#product_name, #version').val('');
        $('#device_name, #android_version').val('');
        $('#filter-form').submit();
    });

    // Toggle admin links
    $('.admin-section h4').on('click', function() {
        toggleAdminLinks();
    });

    // View More functionality
    $(document).on('click', '.view-more-btn', function() {
        const cardDetails = $(this).closest('.card-details');
        cardDetails.find('p').css('-webkit-line-clamp', 'unset');
        $(this).remove();
    });

    // Dynamically show/hide bug type options based on testing result
    $(document).on('change', 'select[name="testing_result"]', function() {
        const bugTypeSelect = $(this).closest('form').find('select[name="bug_type"]');
        if ($(this).val() === 'Fail') {
            bugTypeSelect.html(`
                <option value="Critical">Critical</option>
                <option value="High">High</option>
                <option value="Low">Low</option>
            `);
        } else {
            bugTypeSelect.html('<option value="Nil">Nil</option>');
        }
    });
});
    </script>
</body>
</html>
                            