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

// Ensure only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection for MIS Testing Summary
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "testing_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch MIS Testing Summary
$sql = "SELECT 
            DATE(tested_at) as date,
            COUNT(id) AS total_tests,
            SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
            SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
            SUM(CASE WHEN bug_type = 'Critical' THEN 1 ELSE 0 END) AS critical_bugs,
            SUM(CASE WHEN bug_type = 'High' THEN 1 ELSE 0 END) AS high_bugs,
            SUM(CASE WHEN bug_type = 'Low' THEN 1 ELSE 0 END) AS low_bugs,
            SUM(CASE WHEN cleared_flag = 1 THEN 1 ELSE 0 END) AS fixes_done
        FROM testcase
        WHERE tested_at IS NOT NULL
        GROUP BY DATE(tested_at)
        ORDER BY DATE(tested_at) DESC
        LIMIT 10";

$result = $conn->query($sql);
$testing_summary = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $testing_summary[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Test Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Welcome Message and Start Testing Button */
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .welcome-message h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .welcome-message p {
            margin: 5px 0 0;
            color: #666;
        }

        .start-testing-btn .btn {
            font-size: 16px;
            padding: 10px 20px;
        }

        /* MIS Testing Summary Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        /* Admin Section */
        .admin-section h4 {
            font-size: 16px;
            cursor: pointer;
        }

        .admin-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .admin-links {
            display: none; /* Initially hidden */
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
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="content-container">
            <!-- Welcome Message and Start Testing Button -->
            <div class="welcome-section">
                <div class="welcome-message">
                    <h4>Welcome Back, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h4>
                    <p>Today is <?php echo date('l, F j, Y'); ?>.</p>
                </div>
                <div class="start-testing-btn">
                    <a href="update_tc3.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-play"></i> Start Testing
                    </a>
                </div>
            </div>

            <hr class="my-4">

            <!-- MIS Testing Summary -->
            <h5>📊 MIS Testing Summary</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Tests</th>
                        <th>Passed ✅</th>
                        <th>Failed ❌</th>
                        <th>Critical Bugs 🚨</th>
                        <th>High Bugs ⚠️</th>
                        <th>Low Bugs ⚠️</th>
                        <th>Fixes Done 🛠️</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testing_summary as $row) : ?>
                        <tr>
                            <td><?= $row['date']; ?></td>
                            <td><?= $row['total_tests']; ?></td>
                            <td><?= $row['passed']; ?></td>
                            <td><?= $row['failed']; ?></td>
                            <td><?= $row['critical_bugs']; ?></td>
                            <td><?= $row['high_bugs']; ?></td>
                            <td><?= $row['low_bugs']; ?></td>
                            <td><?= $row['fixes_done']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>