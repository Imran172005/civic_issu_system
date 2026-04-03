<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$role = $_GET['role'] ?? 'solver';
$userIdToEdit = $_GET['id'] ?? null;

$editUser = null;
if ($userIdToEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userIdToEdit]);
    $editUser = $stmt->fetch();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if ($editUser) {
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullName, $phone, $hashedPassword, $userIdToEdit]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$fullName, $phone, $userIdToEdit]);
            }
            $success = "User updated successfully.";
        } else {
            if (empty($password)) {
                $error = "Password is required.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$fullName, $email, $phone, $hashedPassword, $role])) {
                        sendWelcomeEmail($email, $fullName, $password, $role);
                        $success = "User created successfully! Welcome email sent.";
                    } else {
                        $error = "Failed to create user.";
                    }
                }
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
    <title><?= $editUser ? 'Edit' : 'Add' ?> User - Admin Portal</title>
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
            <h4><?= $editUser ? 'Edit' : 'Add' ?> <?= ucfirst($role) ?></h4>
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
                <a href="users.php">Manage Users</a> / <span><?= $editUser ? 'Edit' : 'Add' ?> <?= ucfirst($role) ?></span>
            </nav>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-person-plus"></i> <?= $editUser ? 'Edit' : 'Add New' ?> <?= ucfirst($role) ?></h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="role" value="<?= $role ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= $editUser ? sanitize($editUser['full_name']) : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" name="phone" class="form-control" value="<?= $editUser ? sanitize($editUser['phone']) : '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" value="<?= $editUser ? sanitize($editUser['email']) : '' ?>" <?= $editUser ? 'disabled' : 'required' ?>>
                                <?php if ($editUser): ?><small class="text-muted">Email cannot be changed</small><?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <?= $editUser ? '(leave blank to keep current)' : '*' ?></label>
                                <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-purple">
                            <i class="bi bi-save"></i> <?= $editUser ? 'Update' : 'Create' ?> User
                        </button>
                        <a href="users.php?tab=<?= $role . 's' ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
