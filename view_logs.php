<?php
session_start();
include 'db_config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['is_admin'])) {
    header("Location: summary.php");
    exit();
}

// Define the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Pagination setup
$logsPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logsPerPage;

// Search and filter functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$actionType = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query
$query = "SELECT * FROM `log` WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR action_description LIKE ? OR endpoint LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if (!empty($actionType)) {
    $query .= " AND action_type = ?";
    $params[] = $actionType;
    $types .= 's';
}

if (!empty($dateFrom)) {
    $query .= " AND created_at >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if (!empty($dateTo)) {
    $query .= " AND created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
    $types .= 's';
}

// Get total count for pagination
$countQuery = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
$stmt = $conn->prepare($countQuery);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$totalLogs = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalLogs / $logsPerPage);

// Get logs for current page
$query .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $logsPerPage;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

// Get distinct action types for filter dropdown
$actionTypes = [];
$typeResult = $conn->query("SELECT DISTINCT action_type FROM `log` ORDER BY action_type");
while ($row = $typeResult->fetch_assoc()) {
    $actionTypes[] = $row['action_type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
        .admin-links {
            display: none;
        }

        .log-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .log-card.error {
            border-left-color: #dc3545;
        }

        .log-card.warning {
            border-left-color: #ffc107;
        }

        .log-card.success {
            border-left-color: #28a745;
        }

        .log-card.info {
            border-left-color: #17a2b8;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .log-body {
            padding: 15px;
            display: none;
        }

        .log-body.show {
            display: block;
        }

        .log-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .badge-method {
            font-size: 0.75rem;
            padding: 3px 6px;
        }

        .badge-get {
            background-color: #28a745;
        }

        .badge-post {
            background-color: #007bff;
        }

        .badge-put {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-delete {
            background-color: #dc3545;
        }

        .json-viewer {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .filter-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .content-container {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .sidebar-container {
                width: 100%;
                height: auto;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                margin-right: 0;
                border-radius: 0;
            }
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

        <!-- Main Content -->
        <div class="content-container">
            <h4 class="mb-4">System Logs</h4>
            
            <!-- Filter Section -->
            <div class="filter-container">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="action_type" class="form-select">
                            <option value="">All Action Types</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                    <?php echo $actionType === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="view_logs.php" class="btn btn-secondary w-100">
                            <i class="fas fa-sync"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Logs List -->
            <div class="logs-list">
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php 
                        // Determine card class based on action type or status
                        $cardClass = '';
                        if (strpos($log['action_type'], 'ERROR') !== false || 
                            strpos($log['action_type'], 'FAIL') !== false || 
                            ($log['response_status'] >= 400 && $log['response_status'] < 600)) {
                            $cardClass = 'error';
                        } elseif (strpos($log['action_type'], 'WARN') !== false) {
                            $cardClass = 'warning';
                        } elseif (strpos($log['action_type'], 'SUCCESS') !== false || 
                                 ($log['response_status'] >= 200 && $log['response_status'] < 300)) {
                            $cardClass = 'success';
                        } else {
                            $cardClass = 'info';
                        }

                        // Format timestamp
                        $timestamp = date('M d, Y H:i:s', strtotime($log['created_at']));
                        ?>
                        <div class="log-card <?php echo $cardClass; ?>">
                            <div class="log-header" onclick="toggleLogDetails(this)">
                                <div>
                                    <strong><?php echo htmlspecialchars($log['action_type']); ?></strong>
                                    <span class="badge badge-method badge-<?php echo strtolower($log['http_method']); ?>">
                                        <?php echo htmlspecialchars($log['http_method']); ?>
                                    </span>
                                    <?php if ($log['response_status']): ?>
                                        <span class="badge bg-<?php echo $log['response_status'] >= 400 ? 'danger' : ($log['response_status'] >= 300 ? 'warning' : 'success'); ?>">
                                            <?php echo htmlspecialchars($log['response_status']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="log-meta">
                                        <?php echo htmlspecialchars($log['username'] ? $log['username'] : 'System'); ?> • 
                                        <?php echo $timestamp; ?> • 
                                        <?php echo htmlspecialchars($log['ip_address']); ?>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="log-body">
                                <p><?php echo htmlspecialchars($log['action_description']); ?></p>
                                
                                <div class="mb-3">
                                    <strong>Endpoint:</strong> 
                                    <code><?php echo htmlspecialchars($log['endpoint']); ?></code>
                                </div>
                                
                                <?php if ($log['request_payload']): ?>
                                    <div class="mb-3">
                                        <strong>Request:</strong>
                                        <div class="json-viewer">
                                            <pre><?php echo htmlspecialchars(json_encode(json_decode($log['request_payload']), JSON_PRETTY_PRINT)); ?></pre>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($log['response_data']): ?>
                                    <div class="mb-3">
                                        <strong>Response:</strong>
                                        <div class="json-viewer">
                                            <pre><?php echo htmlspecialchars(json_encode(json_decode($log['response_data']), JSON_PRETTY_PRINT)); ?></pre>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($log['user_agent']): ?>
                                    <div class="mb-3">
                                        <strong>User Agent:</strong>
                                        <div><?php echo htmlspecialchars($log['user_agent']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <nav aria-label="Logs pagination">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="alert alert-info">
                        No logs found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleLogDetails(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('i');
            
            body.classList.toggle('show');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }

        function toggleAdminLinks() {
            const adminLinks = document.querySelector('.admin-links');
            adminLinks.style.display = adminLinks.style.display === 'block' ? 'none' : 'block';
        }

        // Auto-expand log entries with errors
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.log-card.error .log-header').forEach(header => {
                toggleLogDetails(header);
            });
        });
    </script>
</body>
</html>