<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$stmt = $pdo->query("SELECT COUNT(*) FROM issues");
$totalIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'pending'");
$pendingIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'assigned'");
$assignedIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'in_progress'");
$inProgressIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'resolved'");
$resolvedIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE status = 'closed'");
$closedIssues = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM issues GROUP BY category ORDER BY count DESC");
$categoryData = $stmt->fetchAll();

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM issues GROUP BY status");
$statusData = $stmt->fetchAll();

$stmt = $pdo->query("SELECT MONTH(created_at) as month, COUNT(*) as count FROM issues WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY MONTH(created_at) ORDER BY month");
$monthlyData = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM issues ORDER BY created_at DESC LIMIT 10");
$recentIssues = $stmt->fetchAll();

$stmt = $pdo->query("SELECT u.id, u.full_name, COUNT(i.id) as resolved_count FROM users u LEFT JOIN issues i ON u.id = i.assigned_to AND i.status = 'resolved' WHERE u.role = 'solver' GROUP BY u.id ORDER BY resolved_count DESC LIMIT 5");
$topSolvers = $stmt->fetchAll();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar sidebar-admin">
        <div class="sidebar-header">
            <i class="bi bi-shield-check"></i> Admin Portal
        </div>
        <nav>
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="issues.php"><i class="bi bi-inbox"></i> All Issues</a>
            <a href="users.php"><i class="bi bi-people"></i> Manage Users</a>
            <a href="reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
            <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar top-bar-admin">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>Admin Dashboard</h4>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-purple position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">View all notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitize($user['full_name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
        
        <main class="content">
            <div class="row g-4 mb-4">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="bi bi-inbox"></i></div>
                        <div class="stat-info">
                            <h3><?= $totalIssues ?></h3>
                            <p>Total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="bi bi-clock"></i></div>
                        <div class="stat-info">
                            <h3><?= $pendingIssues ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="bi bi-person-plus"></i></div>
                        <div class="stat-info">
                            <h3><?= $assignedIssues ?></h3>
                            <p>Assigned</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="stat-info">
                            <h3><?= $inProgressIssues ?></h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= $resolvedIssues ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-dark"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= $closedIssues ?></h3>
                            <p>Closed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h5>Issues by Category</h5></div>
                        <div class="card-body">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h5>Issues by Status</h5></div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h5>Monthly Trend</h5></div>
                        <div class="card-body">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="bi bi-clock-history"></i> Recent Issues</h5>
                            <a href="issues.php" class="btn btn-sm btn-purple">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentIssues as $issue): ?>
                                            <tr>
                                                <td>#<?= $issue['id'] ?></td>
                                                <td><?= sanitize($issue['title']) ?></td>
                                                <td><span class="badge bg-info"><?= getCategoryLabel($issue['category']) ?></span></td>
                                                <td><span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span></td>
                                                <td><?= formatDate($issue['created_at']) ?></td>
                                                <td><a href="issue-detail.php?id=<?= $issue['id'] ?>" class="btn btn-sm btn-outline-purple">View</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h5><i class="bi bi-trophy"></i> Top Solvers</h5></div>
                        <div class="card-body">
                            <?php if (empty($topSolvers)): ?>
                                <p class="text-muted">No solvers yet.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($topSolvers as $solver): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= sanitize($solver['full_name']) ?>
                                            <span class="badge bg-success rounded-pill"><?= $solver['resolved_count'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map('getCategoryLabel', array_column($categoryData, 'category'))) ?>,
                datasets: [{
                    label: 'Issues',
                    data: <?= json_encode(array_column($categoryData, 'count')) ?>,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map('getStatusLabel', array_column($statusData, 'status'))) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($statusData, 'count')) ?>,
                    backgroundColor: ['#6c757d', '#0d6efd', '#ffc107', '#198754', '#212529']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($m) { return $months[$m['month']-1] ?? ''; }, $monthlyData)) ?>,
                datasets: [{
                    label: 'Issues',
                    data: <?= json_encode(array_column($monthlyData, 'count')) ?>,
                    borderColor: '#6f42c1',
                    fill: false
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>
