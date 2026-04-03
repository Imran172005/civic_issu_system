<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('solver');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $fullName = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $city = sanitize($_POST['city']);
        
        $profilePic = $user['profile_pic'];
        if (!empty($_FILES['profile_pic']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $uploadResult = uploadFile($_FILES['profile_pic'], $allowedTypes, $uploadDir);
            if ($uploadResult['success']) {
                $profilePic = 'uploads/profile/' . $uploadResult['filename'];
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, city = ?, profile_pic = ? WHERE id = ?");
        if ($stmt->execute([$fullName, $phone, $address, $city, $profilePic, $userId])) {
            $_SESSION['name'] = $fullName;
            $success = "Profile updated successfully.";
            $user = getUserById($pdo, $userId);
        } else {
            $error = "Failed to update profile.";
        }
    }
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE assigned_to = ? AND status = 'resolved'");
$stmt->execute([$userId]);
$totalResolved = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(f.rating) as avg_rating FROM feedback f JOIN resolutions r ON f.issue_id = r.issue_id WHERE r.solver_id = ?");
$stmt->execute([$userId]);
$avgRating = $stmt->fetch()['avg_rating'] ?? 0;

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Solver Portal</title>
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
            <a href="assigned-issues.php"><i class="bi bi-list-ul"></i> My Assigned Issues</a>
            <a href="profile.php" class="active"><i class="bi bi-person"></i> Profile</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <header class="top-bar top-bar-solver">
            <button class="btn-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h4>My Profile</h4>
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
            <div class="row">
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <div class="profile-pic-container mb-3">
                                <?php if ($user['profile_pic'] && file_exists(__DIR__ . '/../' . $user['profile_pic'])): ?>
                                    <img src="../<?= $user['profile_pic'] ?>" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="placeholder-pic"><i class="bi bi-person"></i></div>
                                <?php endif; ?>
                            </div>
                            <h4><?= sanitize($user['full_name']) ?></h4>
                            <p class="text-muted"><?= sanitize($user['email']) ?></p>
                            
                            <div class="row mt-4 text-center">
                                <div class="col-6">
                                    <h5><?= $totalResolved ?></h5>
                                    <small class="text-muted">Total Resolved</small>
                                </div>
                                <div class="col-6">
                                    <h5><?= round($avgRating, 1) ?></h5>
                                    <small class="text-muted">Avg Rating</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header"><h5><i class="bi bi-person"></i> Edit Profile</h5></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" value="<?= sanitize($user['full_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= sanitize($user['address']) ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" value="<?= sanitize($user['city']) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" name="profile_pic" class="form-control" accept="image/*">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save"></i> Save Changes
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
    <style>
        .profile-pic-container img {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }
        .placeholder-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .placeholder-pic i {
            font-size: 3rem;
            color: #6c757d;
        }
    </style>
</body>
</html>
