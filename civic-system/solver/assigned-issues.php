<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('solver');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$where = "WHERE assigned_to = ?";
$params = [$userId];

$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}
if ($priorityFilter) {
    $where .= " AND priority = ?";
    $params[] = $priorityFilter;
}
if ($categoryFilter) {
    $where .= " AND category = ?";
    $params[] = $categoryFilter;
}

$stmt = $pdo->prepare("SELECT * FROM issues $where ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'), created_at DESC");
$stmt->execute($params);
$issues = $stmt->fetchAll();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Issues - Solver Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="sidebar sidebar-solver">
        <div class="sidebar-header">
            <i class="bi bi-tools"></i> Solver Portal
        </div>
        <nav>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="assigned-issues.php" class="active"><i class="bi bi-list-ul"></i> My Assigned Issues</a>
            <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar top-bar-solver">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>My Assigned Issues</h4>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-success position-relative" data-bs-toggle="dropdown">
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
                    <a href="assigned-issues.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="assigned" <?= $statusFilter === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="priority" class="form-select">
                                <option value="">All Priority</option>
                                <option value="critical" <?= $priorityFilter === 'critical' ? 'selected' : '' ?>>Critical</option>
                                <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="medium" <?= $priorityFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="road" <?= $categoryFilter === 'road' ? 'selected' : '' ?>>Road Damage</option>
                                <option value="water" <?= $categoryFilter === 'water' ? 'selected' : '' ?>>Water Supply</option>
                                <option value="electricity" <?= $categoryFilter === 'electricity' ? 'selected' : '' ?>>Electricity</option>
                                <option value="sanitation" <?= $categoryFilter === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                                <option value="drainage" <?= $categoryFilter === 'drainage' ? 'selected' : '' ?>>Drainage</option>
                                <option value="street_light" <?= $categoryFilter === 'street_light' ? 'selected' :''
                                ?>>Street Light</option>
                                <option value="other" <?= $categoryFilter === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list-ul"></i> All Assigned Issues (<?= count($issues) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($issues)): ?>
                        <p class="text-muted text-center py-4">No issues found.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($issues as $issue): ?>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-<?= getPriorityColor($issue['priority']) ?>"><?= getPriorityLabel($issue['priority']) ?></span>
                                                <span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span>
                                            </div>
                                            <h6>#<?= $issue['id'] ?> - <?= sanitize($issue['title']) ?></h6>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-geo-alt"></i> <?= sanitize($issue['village']) ?>, <?= sanitize($issue['taluka']) ?>, <?= sanitize($issue['district']) ?>
                                            </p>
                                            <p class="mb-2 text-muted small">
                                                <i class="bi bi-tag"></i> <?= getCategoryLabel($issue['category']) ?>
                                            </p>
                                            <small class="text-muted">Reported: <?= formatDate($issue['created_at']) ?></small>
                                            <a href="issue-work.php?id=<?= $issue['id'] ?>" class="btn btn-sm btn-success float-end">View & Work</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
