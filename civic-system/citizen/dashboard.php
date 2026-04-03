<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE citizen_id = ?");
$stmt->execute([$userId]);
$totalIssues = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE citizen_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingIssues = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE citizen_id = ? AND status = 'in_progress'");
$stmt->execute([$userId]);
$inProgressIssues = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE citizen_id = ? AND status = 'resolved'");
$stmt->execute([$userId]);
$resolvedIssues = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM issues WHERE citizen_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentIssues = $stmt->fetchAll();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Citizen Portal</title>
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
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="report-issue.php"><i class="bi bi-plus-circle"></i> Report Issue</a>
            <a href="my-issues.php"><i class="bi bi-list-ul"></i> My Issues</a>
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
            <h4>Citizen Portal</h4>
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
            <div class="welcome-banner">
                <div class="welcome-content">
                    <h2>Welcome, <?= sanitize($user['full_name']) ?>!</h2>
                    <p>Report and track civic issues in your village</p>
                </div>
                <a href="report-issue.php" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle"></i> Report New Issue
                </a>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="bi bi-inbox"></i></div>
                        <div class="stat-info">
                            <h3><?= $totalIssues ?></h3>
                            <p>Total Issues</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="bi bi-clock"></i></div>
                        <div class="stat-info">
                            <h3><?= $pendingIssues ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="stat-info">
                            <h3><?= $inProgressIssues ?></h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= $resolvedIssues ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Recent Issues</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentIssues)): ?>
                        <p class="text-muted text-center py-4">No issues reported yet. <a href="report-issue.php">Report your first issue</a></p>
                    <?php else: ?>
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
