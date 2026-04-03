<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$where = "1=1";
$params = [];

$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$district = $_GET['district'] ?? '';

if ($statusFilter) {
    $where .= " AND i.status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter) {
    $where .= " AND i.category = ?";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $where .= " AND i.priority = ?";
    $params[] = $priorityFilter;
}
if ($search) {
    $where .= " AND (i.title LIKE ? OR i.id = ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = $search;
    $params[] = "%$search%";
}
if ($dateFrom) {
    $where .= " AND DATE(i.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND DATE(i.created_at) <= ?";
    $params[] = $dateTo;
}
if ($district) {
    $where .= " AND i.district = ?";
    $params[] = $district;
}

$stmt = $pdo->prepare("SELECT i.*, u.full_name as citizen_name FROM issues i JOIN users u ON i.citizen_id = u.id WHERE $where ORDER BY i.created_at DESC");
$stmt->execute($params);
$issues = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_status'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $issueIds = $_POST['issue_ids'] ?? [];
        $newStatus = $_POST['bulk_status'];
        
        if (!empty($issueIds)) {
            $placeholders = implode(',', array_fill(0, count($issueIds), '?'));
            $stmt = $pdo->prepare("UPDATE issues SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$newStatus], $issueIds));
            header('Location: issues.php');
            exit;
        }
    }
}

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Issues - Admin Portal</title>
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
            <a href="issues.php" class="active"><i class="bi bi-inbox"></i> All Issues</a>
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
            <h4>All Issues</h4>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-funnel"></i> Filters</h5>
                    <a href="issues.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="assigned" <?= $statusFilter === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="road" <?= $categoryFilter === 'road' ? 'selected' : '' ?>>Road Damage</option>
                                <option value="water" <?= $categoryFilter === 'water' ? 'selected' : '' ?>>Water Supply</option>
                                <option value="electricity" <?= $categoryFilter === 'electricity' ? 'selected' : '' ?>>Electricity</option>
                                <option value="sanitation" <?= $categoryFilter === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                                <option value="drainage" <?= $categoryFilter === 'drainage' ? 'selected' : '' ?>>Drainage</option>
                                <option value="street_light" <?= $categoryFilter === 'street_light' ? 'selected' : '' ?>>Street Light</option>
                                <option value="other" <?= $categoryFilter === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="priority" class="form-select">
                                <option value="">All Priority</option>
                                <option value="critical" <?= $priorityFilter === 'critical' ? 'selected' : '' ?>>Critical</option>
                                <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-purple w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-inbox"></i> All Issues (<?= count($issues) ?>)</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()"><i class="bi bi-download"></i> CSV</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" id="bulkForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        
                        <div class="mb-3">
                            <select name="bulk_status" class="form-select d-inline-block w-auto">
                                <option value="">Bulk Action</option>
                                <option value="pending">Mark as Pending</option>
                                <option value="assigned">Mark as Assigned</option>
                                <option value="in_progress">Mark as In Progress</option>
                                <option value="resolved">Mark as Resolved</option>
                                <option value="closed">Mark as Closed</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-purple">Apply</button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="issuesTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Citizen</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issues as $issue): ?>
                                        <?php
                                        $assignedTo = null;
                                        if ($issue['assigned_to']) {
                                            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                                            $stmt->execute([$issue['assigned_to']]);
                                            $assignedTo = $stmt->fetchColumn();
                                        }
                                        ?>
                                        <tr>
                                            <td><input type="checkbox" name="issue_ids[]" value="<?= $issue['id'] ?>"></td>
                                            <td>#<?= $issue['id'] ?></td>
                                            <td><?= sanitize($issue['title']) ?></td>
                                            <td><span class="badge bg-info"><?= getCategoryLabel($issue['category']) ?></span></td>
                                            <td><span class="badge bg-<?= getPriorityColor($issue['priority']) ?>"><?= getPriorityLabel($issue['priority']) ?></span></td>
                                            <td><?= sanitize($issue['citizen_name']) ?></td>
                                            <td><?= sanitize($issue['district']) ?></td>
                                            <td><span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span></td>
                                            <td><?= $assignedTo ? sanitize($assignedTo) : '-' ?></td>
                                            <td><?= formatDate($issue['created_at']) ?></td>
                                            <td><a href="issue-detail.php?id=<?= $issue['id'] ?>" class="btn btn-sm btn-outline-purple">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('input[name="issue_ids[]"]').forEach(cb => cb.checked = this.checked);
        });
        
        function exportCSV() {
            let csv = 'ID,Title,Category,Priority,Citizen,Location,Status,Assigned To,Date\n';
            <?php foreach ($issues as $issue): ?>
            csv += '<?= $issue['id'] ?>,"<?= addslashes($issue['title']) ?>","<?= $issue['category'] ?>","<?= $issue['priority'] ?>","<?= addslashes($issue['citizen_name']) ?>","<?= $issue['district'] ?>","<?= $issue['status'] ?>","<?= $assignedTo ?? '' ?>","<?= $issue['created_at'] ?>"\n';
            <?php endforeach; ?>
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'issues.csv';
            a.click();
        }
    </script>
</body>
</html>
