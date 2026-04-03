<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$issueId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM issues WHERE id = ? AND citizen_id = ?");
$stmt->execute([$issueId, $userId]);
$issue = $stmt->fetch();

if (!$issue) {
    header('Location: my-issues.php');
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, u.full_name as solver_name FROM resolutions r JOIN users u ON r.solver_id = u.id WHERE r.issue_id = ?");
$stmt->execute([$issueId]);
$resolution = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM feedback WHERE issue_id = ? AND citizen_id = ?");
$stmt->execute([$issueId, $userId]);
$feedback = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND issue_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId, $issueId]);
$notifications = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$feedback) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $rating = (int)$_POST['rating'];
        $comment = sanitize($_POST['comment']);
        
        if ($rating < 1 || $rating > 5) {
            $error = "Please select a rating.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (issue_id, citizen_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$issueId, $userId, $rating, $comment])) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' AND is_active = 1");
                $admins = $stmt->fetchAll();
                foreach ($admins as $admin) {
                    createNotification($pdo, $admin['id'], $issueId, "New feedback received for issue #$issueId", 'feedback');
                }
                $success = "Thank you for your feedback!";
                header("Location: issue-detail.php?id=$issueId");
                exit;
            }
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
    <title>Issue #<?= $issueId ?> - Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }
        .status-timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e9ecef;
            z-index: 0;
        }
        .status-step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }
        .status-step .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }
        .status-step.completed .step-icon {
            background: #198754;
            color: white;
        }
        .status-step.active .step-icon {
            background: #0d6efd;
            color: white;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
        }
    </style>
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
            <h4>Issue #<?= $issueId ?></h4>
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
            <nav class="breadcrumb mb-3">
                <a href="my-issues.php">My Issues</a> / <span>Issue #<?= $issueId ?></span>
            </nav>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <h5><?= sanitize($issue['title']) ?></h5>
                            <span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="status-timeline">
                                <?php
                                $steps = ['pending' => 'Reported', 'assigned' => 'Assigned', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'];
                                $currentStatus = $issue['status'];
                                $statusOrder = ['pending', 'assigned', 'in_progress', 'resolved'];
                                $currentIndex = array_search($currentStatus, $statusOrder);
                                ?>
                                <?php foreach ($steps as $key => $label): ?>
                                    <?php $stepIndex = array_search($key, $statusOrder); ?>
                                    <div class="status-step <?= $stepIndex <= $currentIndex ? 'completed' : '' ?> <?= $key === $currentStatus ? 'active' : '' ?>">
                                        <div class="step-icon">
                                            <?php if ($stepIndex < $currentIndex): ?>
                                                <i class="bi bi-check"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <small><?= $label ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <h6 class="mt-4">Description</h6>
                            <p><?= nl2br(sanitize($issue['description'])) ?></p>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6>Category</h6>
                                    <span class="badge bg-info"><?= getCategoryLabel($issue['category']) ?></span>
                                </div>
                                <div class="col-md-6">
                                    <h6>Priority</h6>
                                    <span class="badge bg-<?= getPriorityColor($issue['priority']) ?>"><?= getPriorityLabel($issue['priority']) ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Location</h6>
                                <p class="mb-0">
                                    <?= sanitize($issue['address_line']) ?>,
                                    <?= sanitize($issue['village']) ?>,
                                    <?= sanitize($issue['taluka']) ?>,
                                    <?= sanitize($issue['district']) ?>,
                                    <?= sanitize($issue['state']) ?> - <?= sanitize($issue['pincode']) ?>
                                </p>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Reported On</h6>
                                <p><?= formatDate($issue['created_at']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header"><h5><i class="bi bi-camera"></i> Submitted Photo</h5></div>
                        <div class="card-body text-center">
                            <?php if ($issue['photo_path'] && file_exists(__DIR__ . '/../' . $issue['photo_path'])): ?>
                                <img src="../<?= $issue['photo_path'] ?>" class="img-fluid rounded" style="max-height: 400px;">
                            <?php else: ?>
                                <p class="text-muted">No photo available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($resolution): ?>
                        <div class="card mb-4">
                            <div class="card-header"><h5><i class="bi bi-check-circle"></i> Resolution</h5></div>
                            <div class="card-body">
                                <p><?= nl2br(sanitize($resolution['resolution_notes'])) ?></p>
                                <?php if ($resolution['resolved_photo_path'] && file_exists(__DIR__ . '/../' . $resolution['resolved_photo_path'])): ?>
                                    <h6>Resolved Photo</h6>
                                    <img src="../<?= $resolution['resolved_photo_path'] ?>" class="img-fluid rounded" style="max-height: 300px;">
                                <?php endif; ?>
                                <hr>
                                <small class="text-muted">Resolved by <?= sanitize($resolution['solver_name']) ?> on <?= formatDate($resolution['resolved_at']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <?php if ($issue['status'] === 'resolved' && !$feedback): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white"><h5><i class="bi bi-star"></i> Give Feedback</h5></div>
                            <div class="card-body">
                                <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
                                <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
                                
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="star-rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" required>
                                                <label for="star<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Comment (Optional)</label>
                                        <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Submit Feedback</button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($feedback): ?>
                        <div class="card mb-4">
                            <div class="card-header"><h5><i class="bi bi-star"></i> Your Feedback</h5></div>
                            <div class="card-body">
                                <div class="star-rating static">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($feedback['comment']): ?>
                                    <p class="mt-3"><?= sanitize($feedback['comment']) ?></p>
                                <?php endif; ?>
                                <small class="text-muted">Submitted on <?= formatDate($feedback['created_at']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header"><h5><i class="bi bi-bell"></i> Notification History</h5></div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted">No notifications</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notif): ?>
                                        <li class="list-group-item">
                                            <small><?= sanitize($notif['message']) ?></small>
                                            <br><small class="text-muted"><?= formatDate($notif['created_at']) ?></small>
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
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .star-rating input { display: none; }
        .star-rating label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        .star-rating.static {
            display: flex;
            gap: 5px;
        }
    </style>
</body>
</html>
