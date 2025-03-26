<?php
session_start();
include 'log_api.php'; // Include your logging library

// Ensure only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Log page access
logUserAction(
    $_SESSION['emp_id'] ?? null,
    $_SESSION['user'],
    'page_access',
    "Accessed summary page",
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD'],
    null,
    200,
    null,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection for MIS Testing Summary
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "testing_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT 
                DATE(tested_at) as date,
                COUNT(id) AS total_tests,
                SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN bug_type = 'Critical' THEN 1 ELSE 0 END) AS critical_bugs,
                SUM(CASE WHEN bug_type = 'High' THEN 1 ELSE 0 END) AS high_bugs,
                SUM(CASE WHEN bug_type = 'Low' THEN 1 ELSE 0 END) AS low_bugs,
                SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS fixes_done
            FROM testcase
            GROUP BY DATE(tested_at)
            ORDER BY DATE(tested_at) DESC
            LIMIT 3";

    $result = $conn->query($sql);
    $testing_summary = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $testing_summary[] = $row;
        }
    }
    
    // Log successful data fetch
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'data_fetch',
        "Fetched testing summary data",
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        200,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    $conn->close();
} catch (Exception $e) {
    // Log database error
    logUserAction(
        $_SESSION['emp_id'] ?? null,
        $_SESSION['user'],
        'database_error',
        "Database error: " . $e->getMessage(),
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        null,
        500,
        null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    );
    
    die("Error: " . $e->getMessage());
}
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
            display: none;
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
                        <a href="view_logs.php" class="<?php echo ($current_page == 'view_logs.php') ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i> View Logs
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
                    <h3>Welcome Back, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h3>
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
            <h2>📊 MIS Testing Summary</h2>
            <table>
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

    <script>
        // Function to toggle the visibility of admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }
        
        // Log important client-side actions
        function logClientAction(actionType, description) {
            $.ajax({
                url: 'log_api.php',
                type: 'POST',
                data: {
                    action: 'log_client_action',
                    action_type: actionType,
                    description: description
                },
                dataType: 'json'
            });
        }
        
        // Log initial page load
        $(document).ready(function() {
            logClientAction('page_load', 'Loaded summary page');
        });
    </script>
</body>
</html>