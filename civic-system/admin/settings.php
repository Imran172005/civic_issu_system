<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('admin');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$error = '';
$success = '';

$settingsFile = __DIR__ . '/../settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'site_name' => 'Civic Issue Reporting System',
    'contact_email' => 'admin@civic.gov.in',
    'smtp_host' => '',
    'smtp_port' => '',
    'smtp_user' => '',
    'smtp_pass' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $settings['site_name'] = sanitize($_POST['site_name']);
        $settings['contact_email'] = sanitize($_POST['contact_email']);
        $settings['smtp_host'] = sanitize($_POST['smtp_host']);
        $settings['smtp_port'] = sanitize($_POST['smtp_port']);
        $settings['smtp_user'] = sanitize($_POST['smtp_user']);
        $settings['smtp_pass'] = sanitize($_POST['smtp_pass']);
        
        if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
            $success = "Settings saved successfully.";
        } else {
            $error = "Failed to save settings.";
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
    <title>Settings - Admin Portal</title>
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
            <a href="reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
            <a href="settings.php" class="active"><i class="bi bi-gear"></i> Settings</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar top-bar-admin">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>Settings</h4>
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
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header"><h5><i class="bi bi-gear"></i> General Settings</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-control" value="<?= sanitize($settings['site_name']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-control" value="<?= sanitize($settings['contact_email']) ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-purple">
                                    <i class="bi bi-save"></i> Save Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header"><h5><i class="bi bi-envelope"></i> SMTP Settings</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= sanitize($settings['smtp_host']) ?>" placeholder="smtp.gmail.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="text" name="smtp_port" class="form-control" value="<?= sanitize($settings['smtp_port']) ?>" placeholder="587">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_user" class="form-control" value="<?= sanitize($settings['smtp_user']) ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="smtp_pass" class="form-control" value="<?= sanitize($settings['smtp_pass']) ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-purple">
                                    <i class="bi bi-save"></i> Save SMTP Settings
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
