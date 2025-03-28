<?php
session_start();
include 'log_api.php';
include 'db_config.php';

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


try {
    
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Build the test results query - Include pending tests in counts
    $sql = "SELECT 
                DATE(tested_at) as date,
                tested_by_name,
                Product_name,
                Version,
                COUNT(id) AS total_tests,
                SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN testing_result IS NULL THEN 1 ELSE 0 END) AS pending,
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

    // Get data for charts - Include pending tests
    $chart_sql = "SELECT 
                    DATE(tested_at) as date,
                    SUM(CASE WHEN testing_result = 'Pass' THEN 1 ELSE 0 END) AS passed,
                    SUM(CASE WHEN testing_result = 'Fail' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN testing_result IS NULL THEN 1 ELSE 0 END) AS pending,
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

        .sidebar-container.show {
            transform: translateX(0);
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
            overflow-y: auto;
        }

        .content-container.expanded {
            margin-left: 20px;
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

        /* Dashboard specific styles */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            border-radius: 12px;
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        .progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .progress-bar {
            border-radius: 4px;
        }

        /* Status colors */
        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-warning {
            color: #ffc107 !important;
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

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
        }

        /* Header with inline filters */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            margin: 0;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-input {
            width: 180px;
            border-radius: 8px;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }

        .filter-btn {
            border-radius: 8px;
            padding: 8px 15px;
        }

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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-input {
                flex-grow: 1;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1199.98px) {
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

                <?php if ($_SESSION['is_admin']): ?>
                <div class="admin-section">
                    <h4 onclick="toggleAdminLinks()"><i class="fas fa-cogs"></i> Admin <i class="fas fa-chevron-down"></i></h4>
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
            <!-- Header with inline filters -->
            <div class="page-header">
                <h3 class="page-title">Test Summary</h3>
                
                <div class="filter-group">
                    <form id="filterForm" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" class="form-control filter-input" id="filter_name" name="filter_name" 
                               value="<?= htmlspecialchars($filter_name) ?>" placeholder="Tester"
                               list="nameSuggestions" autocomplete="off">
                        <datalist id="nameSuggestions">
                            <?php foreach ($distinct_values['names'] ?? [] as $name): ?>
                                <option value="<?= htmlspecialchars($name) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        
                        <input type="text" class="form-control filter-input" id="filter_product" name="filter_product" 
                               value="<?= htmlspecialchars($filter_product) ?>" placeholder="Product"
                               list="productSuggestions" autocomplete="off">
                        <datalist id="productSuggestions">
                            <?php foreach ($distinct_values['products'] ?? [] as $product): ?>
                                <option value="<?= htmlspecialchars($product) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        
                        <input type="text" class="form-control filter-input" id="filter_version" name="filter_version" 
                               value="<?= htmlspecialchars($filter_version) ?>" placeholder="Version"
                               list="versionSuggestions" autocomplete="off">
                        <datalist id="versionSuggestions">
                            <?php foreach ($distinct_values['versions'] ?? [] as $version): ?>
                                <option value="<?= htmlspecialchars($version) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        
                        <button type="submit" class="btn btn-primary filter-btn">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="summary.php" class="btn btn-outline-secondary filter-btn">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </form>
                </div>
                
                <a href="update_tc3.php" class="btn btn-primary ms-auto">
                    <i class="fas fa-play me-1"></i> Start Testing
                </a>
            </div>

            <!-- Stats Cards - Now with 4 cards including Pending -->
            <div class="row mb-4">
                <div class="col-md-3">
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
                
                <div class="col-md-3">
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
                
                <div class="col-md-3">
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
                
                <div class="col-md-3">
                    <div class="card border-start border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-2">Pending Tests</h6>
                                    <h3 class="mb-0"><?= array_sum(array_column($testing_summary, 'pending')) ?></h3>
                                </div>
                                <div class="bg-warning-light p-3 rounded">
                                    <i class="fas fa-clock text-warning fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts - Now showing pending tests in graphs -->
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

            <!-- Test Results Table - Without pending column -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Test Results</h5>
                    <div>
                        <span class="badge bg-success me-2">Passed: <?= array_sum(array_column($testing_summary, 'passed')) ?></span>
                        <span class="badge bg-danger me-2">Failed: <?= array_sum(array_column($testing_summary, 'failed')) ?></span>
                        <span class="badge bg-warning">Pending: <?= array_sum(array_column($testing_summary, 'pending')) ?></span>
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

        // Initialize charts with pending tests included
        function initCharts() {
            // Trend Chart (Line) with pending tests
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
                            backgroundColor: 'rgba(40, 167, 69, 0.05)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#28a745',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Failed Tests',
                            data: <?= json_encode(array_column($chart_data, 'failed')) ?>,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.05)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#dc3545',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Pending Tests',
                            data: <?= json_encode(array_column($chart_data, 'pending')) ?>,
                            borderColor: '#ffc107',
                            backgroundColor: 'rgba(255, 193, 7, 0.05)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#ffc107',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#eee',
                            borderWidth: 1,
                            padding: 12,
                            usePointStyle: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0.4
                        }
                    }
                }
            });

            // Status Chart (Doughnut) with pending tests
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed', 'Pending'],
                    datasets: [{
                        data: [
                            <?= array_sum(array_column($testing_summary, 'passed')) ?>,
                            <?= array_sum(array_column($testing_summary, 'failed')) ?>,
                            <?= array_sum(array_column($testing_summary, 'pending')) ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#dc3545',
                            '#ffc107'
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#333',
                            bodyColor: '#666',
                            borderColor: '#eee',
                            borderWidth: 1,
                            padding: 12,
                            usePointStyle: true
                        }
                    }
                }
            });
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            
            // Enhance autocomplete for tester name
            const nameInput = document.getElementById('filter_name');
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    const inputValue = this.value.toLowerCase();
                    const suggestions = <?= json_encode($distinct_values['names'] ?? []) ?>;
                    const filteredSuggestions = suggestions.filter(name => 
                        name.toLowerCase().includes(inputValue)
                    );
                    
                    const datalist = document.getElementById('nameSuggestions');
                    datalist.innerHTML = '';
                    
                    filteredSuggestions.forEach(name => {
                        const option = document.createElement('option');
                        option.value = name;
                        datalist.appendChild(option);
                    });
                });
            }
        });
    </script>
</body>
</html>