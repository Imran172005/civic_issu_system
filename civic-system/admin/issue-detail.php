<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$issueId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT i.*, u.full_name as citizen_name, u.email as citizen_email, u.phone as citizen_phone FROM issues i JOIN users u ON i.citizen_id = u.id WHERE i.id = ?");
$stmt->execute([$issueId]);
$issue = $stmt->fetch();

if (!$issue) {
    header('Location: issues.php');
    exit;
}

$assignedToName = null;
if ($issue['assigned_to']) {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$issue['assigned_to']]);
    $assignedToName = $stmt->fetchColumn();
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'solver' ORDER BY full_name");
$solvers = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM resolutions WHERE issue_id = ?");
$stmt->execute([$issueId]);
$resolution = $stmt->fetch();

$stmt = $pdo->prepare("SELECT f.*, u.full_name as citizen_name FROM feedback f JOIN users u ON f.citizen_id = u.id WHERE f.issue_id = ?");
$stmt->execute([$issueId]);
$feedback = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM activity_log WHERE issue_id = ? ORDER BY created_at DESC");
$stmt->execute([$issueId]);
$activityLog = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } elseif (isset($_POST['assign_solver'])) {
        $solverId = $_POST['solver_id'];
        $adminNotes = sanitize($_POST['admin_notes']);
        $priority = $_POST['priority'];
        
        $stmt = $pdo->prepare("UPDATE issues SET assigned_to = ?, priority = ?, admin_notes = ?, status = 'assigned', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$solverId, $priority, $adminNotes, $issueId]);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$solverId]);
        $solver = $stmt->fetch();
        
        createNotification($pdo, $solverId, $issueId, "New issue #$issueId assigned to you: {$issue['title']}", 'assigned');
        sendSolverAssignedEmail($solver['email'], $solver['full_name'], $issueId, $issue['title'], getCategoryLabel($issue['category']), "{$issue['village']}, {$issue['district']}");
        
        createNotification($pdo, $issue['citizen_id'], $issueId, "Your issue #{$issueId} has been assigned to {$solver['full_name']}", 'assigned');
        sendIssueAssignedEmail($issue['citizen_email'], $issue['citizen_name'], $issueId, $issue['title'], $solver['full_name']);
        
        logActivity($pdo, $userId, 'issue_assigned', $issueId, "Assigned to {$solver['full_name']}");
        
        $success = "Issue assigned successfully!";
        header("Location: issue-detail.php?id=$issueId");
        exit;
    } elseif (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE issues SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $issueId]);
        
        if ($newStatus === 'resolved' || $newStatus === 'in_progress') {
            createNotification($pdo, $issue['citizen_id'], $issueId, "Your issue #{$issueId} status is now " . getStatusLabel($newStatus), $newStatus);
            sendStatusUpdateEmail($issue['citizen_email'], $issue['citizen_name'], $issueId, $issue['title'], $newStatus);
        }
        
        logActivity($pdo, $userId, 'status_changed', $issueId, "Status changed to $newStatus");
        
        $success = "Status updated!";
        header("Location: issue-detail.php?id=$issueId");
        exit;
    }
}

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue #<?= $issueId ?> - Admin Portal</title>
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
            <h4>Issue #<?= $issueId ?></h4>
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
            <nav class="breadcrumb mb-3">
                <a href="issues.php">All Issues</a> / <span>Issue #<?= $issueId ?></span>
            </nav>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <h5><?= sanitize($issue['title']) ?></h5>
                            <div>
                                <span class="badge bg-<?= getPriorityColor($issue['priority']) ?> me-2"><?= getPriorityLabel($issue['priority']) ?></span>
                                <span class="badge bg-<?= getStatusColor($issue['status']) ?>"><?= getStatusLabel($issue['status']) ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6>Description</h6>
                            <p><?= nl2br(sanitize($issue['description'])) ?></p>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6>Category</h6>
                                    <span class="badge bg-info"><?= getCategoryLabel($issue['category']) ?></span>
                                </div>
                                <div class="col-md-6">
                                    <h6>Citizen</h6>
                                    <p><?= sanitize($issue['citizen_name']) ?><br><small class="text-muted"><?= sanitize($issue['citizen_email']) ?></small></p>
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
                                <small class="text-muted">Resolved on <?= formatDate($resolution['resolved_at']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($feedback): ?>
                        <div class="card mb-4">
                            <div class="card-header"><h5><i class="bi bi-star"></i> Feedback</h5></div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($feedback['comment']): ?>
                                    <p><?= sanitize($feedback['comment']) ?></p>
                                <?php endif; ?>
                                <small class="text-muted">From <?= sanitize($feedback['citizen_name']) ?> on <?= formatDate($feedback['created_at']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header"><h5><i class="bi bi-activity"></i> Activity Log</h5></div>
                        <div class="card-body">
                            <?php if (empty($activityLog)): ?>
                                <p class="text-muted">No activity</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($activityLog as $log): ?>
                                        <li class="list-group-item">
                                            <strong><?= sanitize($log['action']) ?></strong>
                                            <?php if ($log['details']): ?>: <?= sanitize($log['details']) ?><?php endif; ?>
                                            <br><small class="text-muted"><?= formatDate($log['created_at']) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card mb-4 border-purple">
                        <div class="card-header bg-purple text-white">
                            <h5><i class="bi bi-person-plus"></i> Assign Solver</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Select Solver</label>
                                    <select name="solver_id" class="form-select" required>
                                        <option value="">Select Solver</option>
                                        <?php foreach ($solvers as $solver): ?>
                                            <option value="<?= $solver['id'] ?>" <?= $issue['assigned_to'] == $solver['id'] ? 'selected' : '' ?>><?= sanitize($solver['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-select">
                                        <option value="low" <?= $issue['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                        <option value="medium" <?= $issue['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                        <option value="high" <?= $issue['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                        <option value="critical" <?= $issue['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Notes</label>
                                    <textarea name="admin_notes" class="form-control" rows="2"><?= sanitize($issue['admin_notes']) ?></textarea>
                                </div>
                                
                                <button type="submit" name="assign_solver" class="btn btn-purple w-100">
                                    <i class="bi bi-check"></i> Assign
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header"><h5><i class="bi bi-arrow-repeat"></i> Update Status</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                
                                <div class="mb-3">
                                    <select name="status" class="form-select">
                                        <option value="pending" <?= $issue['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="assigned" <?= $issue['status'] === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                                        <option value="in_progress" <?= $issue['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="resolved" <?= $issue['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option value="closed" <?= $issue['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="update_status" class="btn btn-outline-purple w-100">
                                    <i class="bi bi-save"></i> Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
