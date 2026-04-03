<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$where = "WHERE citizen_id = ?";
$params = [$userId];

$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter) {
    $where .= " AND category = ?";
    $params[] = $categoryFilter;
}
if ($search) {
    $where .= " AND (title LIKE ? OR id = ?)";
    $params[] = "%$search%";
    $params[] = $search;
}
if ($dateFrom) {
    $where .= " AND DATE(created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$stmt = $pdo->prepare("SELECT * FROM issues $where ORDER BY created_at DESC");
$stmt->execute($params);
$issues = $stmt->fetchAll();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Issues - Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-building"></i> Civic Issue
        </div>
        <nav>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="report-issue.php"><i class="bi bi-plus-circle"></i> Report Issue</a>
            <a href="my-issues.php" class="active"><i class="bi bi-list-ul"></i> My Issues</a>
            <a href="notifications.php"><i class="bi bi-bell"></i> Notifications <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?></a>
            <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>My Issues</h4>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-primary position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="notifications.php">View all notifications</a></li>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitize($user['full_name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
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
                    <a href="my-issues.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search by title or ID" value="<?= sanitize($search) ?>">
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
                            <input type="date" name="date_from" class="form-control" value="<?= sanitize($dateFrom) ?>" placeholder="From">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" value="<?= sanitize($dateTo) ?>" placeholder="To">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list-ul"></i> All Issues (<?= count($issues) ?>)</h5>
                    <a href="report-issue.php" class="btn btn-primary"><i class="bi bi-plus"></i> New Issue</a>
                </div>
                <div class="card-body">
                    <?php if (empty($issues)): ?>
                        <p class="text-muted text-center py-4">No issues found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
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
                                            <td>#<?= $issue['id'] ?></td>
                                            <td><?= sanitize($issue['title']) ?></td>
                                            <td><span class="badge bg-info"><?= getCategoryLabel($issue['category']) ?></span></td>
                                            <td><span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span></td>
                                            <td><?= $assignedTo ? sanitize($assignedTo) : '<span class="text-muted">Unassigned</span>' ?></td>
                                            <td><?= formatDate($issue['created_at']) ?></td>
                                            <td><a href="issue-detail.php?id=<?= $issue['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
