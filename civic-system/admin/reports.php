<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$district = $_GET['district'] ?? '';
$category = $_GET['category'] ?? '';
$solverId = $_GET['solver_id'] ?? '';

$where = "WHERE i.created_at BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo . ' 23:59:59'];

if ($district) {
    $where .= " AND i.district = ?";
    $params[] = $district;
}
if ($category) {
    $where .= " AND i.category = ?";
    $params[] = $category;
}
if ($solverId) {
    $where .= " AND i.assigned_to = ?";
    $params[] = $solverId;
}

$stmt = $pdo->prepare("SELECT i.*, u.full_name as citizen_name, s.full_name as solver_name FROM issues i JOIN users u ON i.citizen_id = u.id LEFT JOIN users s ON i.assigned_to = s.id $where ORDER BY i.created_at DESC");
$stmt->execute($params);
$issues = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT district FROM issues WHERE district IS NOT NULL AND district != '' ORDER BY district");
$districts = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'solver' ORDER BY full_name");
$stmt->execute();
$solvers = $stmt->fetchAll();

$total = count($issues);
$pending = count(array_filter($issues, fn($i) => $i['status'] === 'pending'));
$resolved = count(array_filter($issues, fn($i) => $i['status'] === 'resolved'));
$closed = count(array_filter($issues, fn($i) => $i['status'] === 'closed'));

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width-width, initial-scale=1.0">
    <title>Reports - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar sidebar-admin">
        <div class="sidebar-header">
            <i class="bi bi-shield-check"></i> Admin Portal
        </div>
        <nav>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="issues.php"><i class="bi bi-inbox"></i> All Issues</a>
            <a href="users.php"><i class="bi bi-people"></i> Manage Users</a>
            <a href="reports.php" class="active"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
            <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar top-bar-admin">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>Reports</h4>
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
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-funnel"></i> Filter Reports</h5></div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">District</label>
                            <select name="district" class="form-select">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?= $d ?>" <?= $district === $d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="road" <?= $category === 'road' ? 'selected' : '' ?>>Road</option>
                                <option value="water" <?= $category === 'water' ? 'selected' : '' ?>>Water</option>
                                <option value="electricity" <?= $category === 'electricity' ? 'selected' : '' ?>>Electricity</option>
                                <option value="sanitation" <?= $category === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                                <option value="drainage" <?= $category === 'drainage' ? 'selected' : '' ?>>Drainage</option>
                                <option value="street_light" <?= $category === 'street_light' ? 'selected' : '' ?>>Street Light</option>
                                <option value="other" <?= $category === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Solver</label>
                            <select name="solver_id" class="form-select">
                                <option value="">All Solvers</option>
                                <?php foreach ($solvers as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $solverId == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-purple w-100"><i class="bi bi-search"></i> Generate</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="bi bi-inbox"></i></div>
                        <div class="stat-info">
                            <h3><?= $total ?></h3>
                            <p>Total Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="bi bi-clock"></i></div>
                        <div class="stat-info">
                            <h3><?= $pending ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= $resolved ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-dark"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= $closed ?></h3>
                            <p>Closed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-table"></i> Report Data (<?= count($issues) ?>)</h5>
                    <button class="btn btn-sm btn-purple" onclick="exportExcel()"><i class="bi bi-download"></i> Excel</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="reportTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Citizen</th>
                                    <th>District</th>
                                    <th>Solver</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issues as $issue): ?>
                                    <tr>
                                        <td>#<?= $issue['id'] ?></td>
                                        <td><?= sanitize($issue['title']) ?></td>
                                        <td><?= getCategoryLabel($issue['category']) ?></td>
                                        <td><?= sanitize($issue['citizen_name']) ?></td>
                                        <td><?= sanitize($issue['district']) ?></td>
                                        <td><?= $issue['solver_name'] ? sanitize($issue['solver_name']) : '-' ?></td>
                                        <td><span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span></td>
                                        <td><?= formatDate($issue['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function exportExcel() {
            const table = document.getElementById('reportTable');
            let csv = [];
            for (const row of table.rows) {
                const cols = [];
                for (const cell of row.cells) {
                    cols.push(cell.innerText.replace(/"/g, '""'));
                }
                csv.push(cols.join(','));
            }
            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = 'report.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.click();
        }
    </script>
</body>
</html>
