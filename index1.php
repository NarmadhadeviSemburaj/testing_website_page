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
    <title>Test Case Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-color: green; /* Change edit button color to green */
            border-color: green;
        }
        .tick-icon {
            color: green;
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
            padding: 6px 12px; /* Smaller padding for dropdowns */
        }
        .filter-row .btn {
            flex: 0 0 auto;
            font-size: 14px; /* Smaller button font size */
            padding: 6px 12px; /* Smaller padding for button */
        }
        
        .product-checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .product-checkbox-grid .form-check {
            flex: 1 1 calc(50% - 10px); /* Two products per row */
        }
        /* Add your existing CSS here */
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
            right: 100px; /* Position it to the left of the add button */
            background-color: #28a745; /* Green color for upload button */
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <h3>TCM</h3>
            <!-- + Icon for Adding Test Case -->
            <button class="btn btn-primary add-testcase-btn" data-bs-toggle="modal" data-bs-target="#testCaseModal">
                <i class="fas fa-plus"></i>
            </button>

            <!-- Upload Excel Button -->
            <button class="btn btn-success upload-excel-btn" onclick="document.getElementById('excel_file').click()">
                <i class="fas fa-upload"></i>
            </button>
            <input type="file" id="excel_file" name="excel_file" accept=".xls, .xlsx" style="display: none;">

            <!-- Filters for Product and Version -->
            <form method="POST" class="mb-4">
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
                    $sql .= " AND Product_name = '$selected_product'";
                }
                if (!empty($selected_version)) {
                    $sql .= " AND Version = '$selected_version'";
                }
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
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
                                        <button class="btn btn-warning btn-sm edit-btn" data-id="' . $row['id'] . '" data-bs-toggle="modal" data-bs-target="#testCaseModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="delete_testcase1.php?id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                              </div>';
                    }
                } else {
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
                                <input type="hidden" id="testcase_id" name="testcase_id">
                                
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
                                                    <input class='form-check-input' type='checkbox' name='product_name[]' value='$folderName' id='product_$folderName'>
                                                    <label class='form-check-label' for='product_$folderName'>$folderName</label>
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
                                    <input type="text" class="form-control" id="module_name" name="module_name" required>
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" required></textarea>
                                </div>

                                <!-- Preconditions -->
                                <div class="mb-3">
                                    <label for="preconditions" class="form-label">Preconditions</label>
                                    <textarea class="form-control" id="preconditions" name="preconditions"></textarea>
                                </div>

                                <!-- Test Steps -->
                                <div class="mb-3">
                                    <label for="test_steps" class="form-label">Test Steps</label>
                                    <textarea class="form-control" id="test_steps" name="test_steps" required></textarea>
                                </div>

                                <!-- Expected Results -->
                                <div class="mb-3">
                                    <label for="expected_results" class="form-label">Expected Results</label>
                                    <textarea class="form-control" id="expected_results" name="expected_results" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Test Case</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }

        // Handle Excel file upload
        document.getElementById("excel_file").addEventListener("change", function() {
            let fileInput = this;
            if (fileInput.files.length === 0) {
                alert("No file selected.");
                return;
            }

            let formData = new FormData();
            formData.append("excel_file", fileInput.files[0]);

            console.log("Uploading file:", fileInput.files[0].name); // Debugging

            fetch("upload_excel.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    location.reload(); // Refresh to show new data
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while uploading the file.");
            });
        });

        // Handle Edit Button Click
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const testCaseId = this.getAttribute('data-id');
                fetch(`fetch_testcase1.php?id=${testCaseId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            // Populate the modal form with the fetched data
                            document.getElementById('testcase_id').value = data.data.id;
                            document.getElementById('module_name').value = data.data.Module_name;
                            document.getElementById('description').value = data.data.description;
                            document.getElementById('preconditions').value = data.data.preconditions || '';
                            document.getElementById('test_steps').value = data.data.test_steps;
                            document.getElementById('expected_results').value = data.data.expected_results;

                            // Set the selected product and version
                            const productCheckboxes = document.querySelectorAll('.product-checkbox-grid .form-check-input');
                            productCheckboxes.forEach(checkbox => {
                                checkbox.checked = data.data.Product_name.includes(checkbox.value);
                            });

                            const versionSelect = document.getElementById('version');
                            versionSelect.innerHTML = `<option value="${data.data.Version}" selected>${data.data.Version}</option>`;
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching test case:", error);
                        alert("An error occurred while fetching the test case.");
                    });
            });
        });

        // Handle Form Submission
        document.getElementById('testCaseForm').addEventListener('submit', function(event) {
            event.preventDefault();

            // Gather form data
            const formData = {
                testcase_id: document.getElementById('testcase_id').value,
                product_name: Array.from(document.querySelectorAll('.product-checkbox-grid .form-check-input:checked'))
                    .map(checkbox => checkbox.value),
                version: document.getElementById('version').value,
                module_name: document.getElementById('module_name').value,
                description: document.getElementById('description').value,
                preconditions: document.getElementById('preconditions').value,
                test_steps: document.getElementById('test_steps').value,
                expected_results: document.getElementById('expected_results').value
            };

            // Send data to the API
            fetch('submit_testcase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    location.reload(); // Refresh to show new data
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while saving the test case.");
            });
        });

        // Fetch versions for selected products
        document.querySelectorAll('.product-checkbox-grid .form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
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
                                    versionSelect.innerHTML += `<option value="${version}">${version}</option>`;
                                });
                            } else {
                                versionSelect.innerHTML = '<option value="">No versions found</option>';
                            }
                        })
                        .catch(error => {
                            console.error("Error fetching versions:", error);
                            alert("An error occurred while fetching versions.");
                        });
                } else {
                    const versionSelect = document.getElementById('version');
                    versionSelect.innerHTML = '<option value="">Select Version</option>';
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>