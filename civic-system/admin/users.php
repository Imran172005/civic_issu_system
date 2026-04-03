<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$tab = $_GET['tab'] ?? 'citizens';

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'citizen' ORDER BY created_at DESC");
$stmt->execute();
$citizens = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'solver' ORDER BY created_at DESC");
$stmt->execute();
$solvers = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $targetUserId = $_POST['user_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = $currentStatus ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $targetUserId]);
    header('Location: users.php?tab=' . $tab);
    exit;
}

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Portal</title>
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
            <a href="users.php" class="active"><i class="bi bi-people"></i> Manage Users</a>
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
            <h4>Manage Users</h4>
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
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'citizens' ? 'active' : '' ?>" href="?tab=citizens">Citizens (<?= count($citizens) ?>)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'solvers' ? 'active' : '' ?>" href="?tab=solvers">Solvers (<?= count($solvers) ?>)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'admins' ? 'active' : '' ?>" href="?tab=admins">Admins (<?= count($admins) ?>)</a>
                </li>
            </ul>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <?php if ($tab === 'solvers'): ?>
                    <a href="add-user.php?role=solver" class="btn btn-purple"><i class="bi bi-plus"></i> Add Solver</a>
                <?php elseif ($tab === 'admins'): ?>
                    <a href="add-user.php?role=admin" class="btn btn-purple"><i class="bi bi-plus"></i> Add Admin</a>
                <?php else: ?>
                    <h5>Citizens</h5>
                <?php endif; ?>
            </div>
            
            <?php if ($tab === 'citizens'): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>City</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($citizens as $u): ?>
                                        <tr>
                                            <td><?= $u['id'] ?></td>
                                            <td><?= sanitize($u['full_name']) ?></td>
                                            <td><?= sanitize($u['email']) ?></td>
                                            <td><?= sanitize($u['phone']) ?></td>
                                            <td><?= sanitize($u['city']) ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $u['is_active'] ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?= formatDate($u['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab === 'solvers'): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>City</th>
                                        <th>Issues Resolved</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solvers as $solver): ?>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to = ? AND status = 'resolved'");
                                        $stmt->execute([$solver['id']]);
                                        $resolvedCount = $stmt->fetchColumn();
                                        ?>
                                        <tr>
                                            <td><?= $solver['id'] ?></td>
                                            <td><?= sanitize($solver['full_name']) ?></td>
                                            <td><?= sanitize($solver['email']) ?></td>
                                            <td><?= sanitize($solver['phone']) ?></td>
                                            <td><?= sanitize($solver['city']) ?></td>
                                            <td><span class="badge bg-success"><?= $resolvedCount ?></span></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="user_id" value="<?= $solver['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $solver['is_active'] ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $solver['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $solver['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><a href="add-user.php?role=solver&id=<?= $solver['id'] ?>" class="btn btn-sm btn-outline-purple">Edit</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $u): ?>
                                        <tr>
                                            <td><?= $u['id'] ?></td>
                                            <td><?= sanitize($u['full_name']) ?></td>
                                            <td><?= sanitize($u['email']) ?></td>
                                            <td><?= sanitize($u['phone']) ?></td>
                                            <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                            <td><?= formatDate($u['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
