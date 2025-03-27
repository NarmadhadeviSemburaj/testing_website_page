<?php
session_start();
include 'log_api.php';

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

$current_page = basename($_SERVER['PHP_SELF']);

// Get filter parameters
$filter_name = $_GET['filter_name'] ?? '';
$filter_product = $_GET['filter_product'] ?? '';
$filter_version = $_GET['filter_version'] ?? '';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "testing_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Build the test results query
    $sql = "SELECT 
                DATE(tested_at) as date,
                tested_by_name,
                Product_name,
                Version,
                COUNT(id) AS total_tests,
                SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN bug_type = 'Critical' THEN 1 ELSE 0 END) AS critical_bugs,
                SUM(CASE WHEN bug_type = 'High' THEN 1 ELSE 0 END) AS high_bugs,
                SUM(CASE WHEN bug_type = 'Low' THEN 1 ELSE 0 END) AS low_bugs
            FROM testcase
            WHERE tested_by_name IS NOT NULL";

    if (!empty($filter_name)) {
        $sql .= " AND tested_by_name LIKE '%" . $conn->real_escape_string($filter_name) . "%'";
    }
    if (!empty($filter_product)) {
        $sql .= " AND Product_name LIKE '%" . $conn->real_escape_string($filter_product) . "%'";
    }
    if (!empty($filter_version)) {
        $sql .= " AND Version LIKE '%" . $conn->real_escape_string($filter_version) . "%'";
    }

    $sql .= " GROUP BY DATE(tested_at), tested_by_name, Product_name, Version
              ORDER BY DATE(tested_at) DESC
              LIMIT 100";

    $result = $conn->query($sql);
    $testing_summary = $result->fetch_all(MYSQLI_ASSOC);

    // Get data for charts
    $chart_sql = "SELECT 
                    DATE(tested_at) as date,
                    SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
                    SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
                    COUNT(id) AS total_tests
                FROM testcase
                WHERE tested_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND tested_by_name IS NOT NULL
                GROUP BY DATE(tested_at)
                ORDER BY DATE(tested_at) ASC";
    
    $chart_result = $conn->query($chart_sql);
    $chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);

    // Get distinct values for filter suggestions
    $distinct_values = [];
    $distinct_sql = "SELECT 
                        DISTINCT tested_by_name as name,
                        Product_name as product,
                        Version as version
                     FROM testcase
                     WHERE tested_by_name IS NOT NULL";
    $distinct_result = $conn->query($distinct_sql);
    
    if ($distinct_result->num_rows > 0) {
        while ($row = $distinct_result->fetch_assoc()) {
            $distinct_values['names'][] = $row['name'];
            $distinct_values['products'][] = $row['product'];
            $distinct_values['versions'][] = $row['version'];
        }
        
        $distinct_values['names'] = array_unique(array_filter($distinct_values['names']));
        $distinct_values['products'] = array_unique(array_filter($distinct_values['products']));
        $distinct_values['versions'] = array_unique(array_filter($distinct_values['versions']));
    }
    
    $conn->close();
} catch (Exception $e) {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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
            display: flex;
            align-items: center;
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

        .separator {
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }

        .admin-section h4 {
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            transition: background-color 0.3s;
        }

        .admin-section h4:hover {
            background-color: #007bff;
            color: #fff;
        }

        .admin-links {
            display: none;
        }

        .admin-links a {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            text-decoration: none;
            color: #333;
            border-radius: 10px;
            transition: background-color 0.3s;
        }

        .admin-links a:hover {
            background-color: #007bff;
            color: #fff;
        }

        .admin-links a i {
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

        /* Dashboard specific styles */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }

        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .progress {
            height: 8px;
            background-color: #e9ecef;
        }

        /* Status colors */
        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .bg-primary-light {
            background-color: rgba(0, 123, 255, 0.1);
        }

        @media (max-width: 992px) {
            .sidebar-container {
                transform: translateX(-100%);
                z-index: 1000;
            }
            .content-container {
                margin-left: 0;
            }
            .sidebar-container.show {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar-container" id="sidebarContainer">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <h4><?= htmlspecialchars($_SESSION['user']) ?></h4>
            </div>
            
            <div class="sidebar">
                <a href="summary.php" class="<?= ($current_page == 'summary.php') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="update_tc3.php" class="<?= ($current_page == 'update_tc3.php') ? 'active' : '' ?>">
                    <i class="fas fa-vial"></i> Testing
                </a>
                <a href="bug_details.php" class="<?= ($current_page == 'bug_details.php') ? 'active' : '' ?>">
                    <i class="fas fa-bug"></i> Bug Reports
                </a>
                <a href="logout.php" class="text-danger <?= ($current_page == 'logout.php') ? 'active' : '' ?>">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

                <div class="separator"></div>

                <?php if ($_SESSION['is_admin']): ?>
                <div class="admin-section">
                    <h4 onclick="toggleAdminLinks()">Admin</h4>
                    <div class="admin-links">
                        <a href="employees.php" class="<?= ($current_page == 'employees.php') ? 'active' : '' ?>">
                            <i class="fas fa-users"></i> Employees
                        </a>
                        <a href="apk_up.php" class="<?= ($current_page == 'apk_up.php') ? 'active' : '' ?>">
                            <i class="fas fa-upload"></i> APK Admin
                        </a>
                        <a href="index1.php" class="<?= ($current_page == 'index1.php') ? 'active' : '' ?>">
                            <i class="fas fa-list-alt"></i> TCM
                        </a>
                        <a href="view_logs.php" class="<?= ($current_page == 'view_logs.php') ? 'active' : '' ?>">
                            <i class="fas fa-clipboard-list"></i> View Logs
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-container" id="contentContainer">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Test Summary</h3>
                <a href="update_tc3.php" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i> Start Testing
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-start border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Total Tests</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($testing_summary, 'total_tests')) ?></h3>
                                </div>
                                <div class="bg-primary-light p-3 rounded">
                                    <i class="fas fa-vial text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-start border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Passed Tests</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($testing_summary, 'passed')) ?></h3>
                                </div>
                                <div class="bg-success-light p-3 rounded">
                                    <i class="fas fa-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-start border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Failed Tests</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($testing_summary, 'failed')) ?></h3>
                                </div>
                                <div class="bg-danger-light p-3 rounded">
                                    <i class="fas fa-times-circle text-danger fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Test Results Trend (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Test Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Test Results</h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="filter_name" class="form-label">Tester Name</label>
                            <input type="text" class="form-control" id="filter_name" name="filter_name" 
                                   value="<?= htmlspecialchars($filter_name) ?>" placeholder="Filter by name"
                                   list="nameSuggestions">
                            <datalist id="nameSuggestions">
                                <?php foreach ($distinct_values['names'] ?? [] as $name): ?>
                                    <option value="<?= htmlspecialchars($name) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_product" class="form-label">Product</label>
                            <input type="text" class="form-control" id="filter_product" name="filter_product" 
                                   value="<?= htmlspecialchars($filter_product) ?>" placeholder="Filter by product"
                                   list="productSuggestions">
                            <datalist id="productSuggestions">
                                <?php foreach ($distinct_values['products'] ?? [] as $product): ?>
                                    <option value="<?= htmlspecialchars($product) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label for="filter_version" class="form-label">Version</label>
                            <input type="text" class="form-control" id="filter_version" name="filter_version" 
                                   value="<?= htmlspecialchars($filter_version) ?>" placeholder="Filter by version"
                                   list="versionSuggestions">
                            <datalist id="versionSuggestions">
                                <?php foreach ($distinct_values['versions'] ?? [] as $version): ?>
                                    <option value="<?= htmlspecialchars($version) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                            <a href="summary.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Results Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Test Results</h5>
                    <div>
                        <span class="badge bg-success me-2">Passed: <?= array_sum(array_column($testing_summary, 'passed')) ?></span>
                        <span class="badge bg-danger">Failed: <?= array_sum(array_column($testing_summary, 'failed')) ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Tester</th>
                                    <th>Product</th>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Passed</th>
                                    <th>Failed</th>
                                    <th>Bugs</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testing_summary as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= htmlspecialchars($row['tested_by_name']) ?></td>
                                        <td><?= htmlspecialchars($row['Product_name']) ?></td>
                                        <td><?= htmlspecialchars($row['Version']) ?></td>
                                        <td>
                                            <?php 
                                            $pass_rate = ($row['total_tests'] > 0) ? ($row['passed'] / $row['total_tests']) * 100 : 0;
                                            if ($pass_rate >= 90) {
                                                echo '<span class="badge bg-success">Excellent</span>';
                                            } elseif ($pass_rate >= 70) {
                                                echo '<span class="badge bg-primary">Good</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Needs Work</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-success fw-bold"><?= $row['passed'] ?></td>
                                        <td class="text-danger fw-bold"><?= $row['failed'] ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?= $row['critical_bugs'] ?> Critical</span>
                                            <span class="badge bg-warning"><?= $row['high_bugs'] ?> High</span>
                                            <span class="badge bg-secondary"><?= $row['low_bugs'] ?> Low</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?= ($row['passed'] / $row['total_tests']) * 100 ?>%" 
                                                     aria-valuenow="<?= $row['passed'] ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="<?= $row['total_tests'] ?>">
                                                </div>
                                            </div>
                                            <small class="text-muted"><?= number_format(($row['passed'] / $row['total_tests']) * 100, 1) ?>%</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Toggle admin links
        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }

        // Initialize charts
        function initCharts() {
            // Trend Chart (Line)
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($chart_data, 'date')) ?>,
                    datasets: [
                        {
                            label: 'Passed Tests',
                            data: <?= json_encode(array_column($chart_data, 'passed')) ?>,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Failed Tests',
                            data: <?= json_encode(array_column($chart_data, 'failed')) ?>,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Status Chart (Doughnut)
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [
                            <?= array_sum(array_column($testing_summary, 'passed')) ?>,
                            <?= array_sum(array_column($testing_summary, 'failed')) ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
        });
    </script>
</body>
</html>