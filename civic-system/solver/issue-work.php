<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

checkAuth('solver');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$issueId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT i.*, u.full_name as citizen_name, u.email as citizen_email FROM issues i JOIN users u ON i.citizen_id = u.id WHERE i.id = ? AND i.assigned_to = ?");
$stmt->execute([$issueId, $userId]);
$issue = $stmt->fetch();

if (!$issue) {
    header('Location: assigned-issues.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $newStatus = $_POST['status'];
        
        if ($newStatus === 'in_progress') {
            $stmt = $pdo->prepare("UPDATE issues SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$issueId]);
            
            createNotification($pdo, $issue['citizen_id'], $issueId, "Your issue #{$issueId} is now in progress.", 'in_progress');
            sendStatusUpdateEmail($issue['citizen_email'], $issue['citizen_name'], $issueId, $issue['title'], 'in_progress');
            logActivity($pdo, $userId, 'status_changed', $issueId, "Status changed to in_progress");
            
            $success = "Status updated to In Progress.";
            header("Location: issue-work.php?id=$issueId");
            exit;
        } elseif ($newStatus === 'resolved') {
            if (empty($_FILES['resolved_photo']['name'])) {
                $error = "Resolution photo is required.";
            } else {
                $uploadDir = __DIR__ . '/../uploads/resolved/';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $uploadResult = uploadFile($_FILES['resolved_photo'], $allowedTypes, $uploadDir);
                
                if (!$uploadResult['success']) {
                    $error = $uploadResult['error'];
                } else {
                    $resolutionNotes = sanitize($_POST['resolution_notes']);
                    
                    $stmt = $pdo->prepare("INSERT INTO resolutions (issue_id, solver_id, resolved_photo_path, resolution_notes, resolved_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$issueId, $userId, 'uploads/resolved/' . $uploadResult['filename'], $resolutionNotes]);
                    
                    $stmt = $pdo->prepare("UPDATE issues SET status = 'resolved', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$issueId]);
                    
                    createNotification($pdo, $issue['citizen_id'], $issueId, "Your issue #{$issueId} has been resolved. Please check and give feedback.", 'resolved');
                    sendIssueResolvedEmail($issue['citizen_email'], $issue['citizen_name'], $issueId, $issue['title'], $user['full_name']);
                    logActivity($pdo, $userId, 'issue_resolved', $issueId, "Issue resolved");
                    
                    $success = "Issue marked as resolved!";
                    header("Location: issue-work.php?id=$issueId");
                    exit;
                }
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM resolutions WHERE issue_id = ?");
$stmt->execute([$issueId]);
$resolution = $stmt->fetch();

$unreadCount = getUnreadNotificationsCount($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work on Issue #<?= $issueId ?> - Solver Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #198754;
            background: #f8f9fa;
        }
        #imagePreview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            display: none;
        }
    </style>
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
            <h4>Issue #<?= $issueId ?></h4>
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
            <nav class="breadcrumb mb-3">
                <a href="assigned-issues.php">My Assigned Issues</a> / <span>Issue #<?= $issueId ?></span>
            </nav>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            
            <?php if ($issue['status'] === 'resolved'): ?>
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle"></i> This issue has been resolved</h5>
                </div>
            <?php endif; ?>
            
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
                                    <h6>Reported By</h6>
                                    <p><?= sanitize($issue['citizen_name']) ?></p>
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
                        <div class="card-header"><h5><i class="bi bi-camera"></i> Citizen's Submitted Photo</h5></div>
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
                            <div class="card-header"><h5><i class="bi bi-check-circle"></i> Resolution Details</h5></div>
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
                </div>
                
                <div class="col-lg-4">
                    <?php if ($issue['status'] !== 'resolved'): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h5><i class="bi bi-tools"></i> Work on Issue</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="workForm">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Update Status</label>
                                        <select name="status" class="form-select" id="statusSelect" required>
                                            <?php if ($issue['status'] === 'assigned'): ?>
                                                <option value="">Select Action</option>
                                                <option value="in_progress">Start Working</option>
                                            <?php endif; ?>
                                            <?php if ($issue['status'] === 'assigned' || $issue['status'] === 'in_progress'): ?>
                                                <option value="resolved">Mark as Resolved</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div id="resolutionFields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Resolution Photo *</label>
                                            <div class="drop-zone" id="dropZone">
                                                <i class="bi bi-cloud-upload fs-1"></i>
                                                <p class="mb-2">Upload resolution photo</p>
                                                <small class="text-muted">JPG, PNG only. Max 5MB</small>
                                                <input type="file" name="resolved_photo" id="photoInput" accept="image/jpeg,image/png,image/jpg" hidden>
                                            </div>
                                            <img id="imagePreview" class="mt-2 w-100">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Resolution Notes</label>
                                            <textarea name="resolution_notes" class="form-control" rows="4" placeholder="Describe the work done..."></textarea>
                                        </div>
                                        
                                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                            <i class="bi bi-check-circle"></i> Confirm Resolution
                                        </button>
                                    </div>
                                    
                                    <div class="modal fade" id="confirmModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Resolution</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to mark this issue as resolved?</p>
                                                    <p class="text-muted">The citizen will be notified and asked for feedback.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Confirm</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('statusSelect');
            const resolutionFields = document.getElementById('resolutionFields');
            
            // Check initial status and show fields if needed
            if (statusSelect.value === 'resolved') {
                resolutionFields.style.display = 'block';
            }
            
            statusSelect.addEventListener('change', function() {
                if (this.value === 'resolved') {
                    resolutionFields.style.display = 'block';
                } else if (this.value === 'in_progress') {
                    document.getElementById('workForm').submit();
                } else {
                    resolutionFields.style.display = 'none';
                }
            });
        });
        
        const dropZone = document.getElementById('dropZone');
        const photoInput = document.getElementById('photoInput');
        const imagePreview = document.getElementById('imagePreview');
        
        dropZone.addEventListener('click', () => photoInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                photoInput.files = e.dataTransfer.files;
                previewImage(e.dataTransfer.files[0]);
            }
        });
        
        photoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                previewImage(e.target.files[0]);
            }
        });
        
        function previewImage(file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB');
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>
