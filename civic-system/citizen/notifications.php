<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    header('Location: notifications.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Citizen Portal</title>
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
            <a href="my-issues.php"><i class="bi bi-list-ul"></i> My Issues</a>
            <a href="notifications.php" class="active"><i class="bi bi-bell"></i> Notifications <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?></a>
            <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>Notifications</h4>
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-bell"></i> All Notifications (<?= count($notifications) ?>)</h5>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check-all"></i> Mark all as read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center py-4">No notifications yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notif): ?>
                                <?php
                                $link = $notif['issue_id'] ? "issue-detail.php?id={$notif['issue_id']}" : '#';
                                $iconClass = ['assigned' => 'bi-person-plus', 'in_progress' => 'bi-arrow-repeat', 'resolved' => 'bi-check-circle', 'feedback' => 'bi-star'][$notif['type']] ?? 'bi-bell';
                                ?>
                                <a href="<?= $link ?>" class="list-group-item list-group-item-action <?= !$notif['is_read'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="notification-icon">
                                                <i class="bi <?= $iconClass ?>"></i>
                                            </div>
                                            <div>
                                                <p class="mb-1"><?= sanitize($notif['message']) ?></p>
                                                <small class="text-muted"><?= formatDate($notif['created_at']) ?></small>
                                            </div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge bg-primary">New</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <style>
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</body>
</html>
