<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');
$userId = $_SESSION['user_id'];
$user = getUserById($pdo, $userId);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $category = $_POST['category'];
        $priority = $_POST['priority'];
        $addressLine = sanitize($_POST['address_line']);
        $village = sanitize($_POST['village']);
        $taluka = sanitize($_POST['taluka']);
        $district = sanitize($_POST['district']);
        $state = $_POST['state'];
        $pincode = sanitize($_POST['pincode']);
        $landmark = sanitize($_POST['landmark']);
        
        if (empty($_FILES['photo']['name'])) {
            $error = "Photo is required.";
        } else {
            $uploadDir = __DIR__ . '/../uploads/issues/';
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $uploadResult = uploadFile($_FILES['photo'], $allowedTypes, $uploadDir);
            
            if (!$uploadResult['success']) {
                $error = $uploadResult['error'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO issues (citizen_id, title, description, category, priority, address_line, village, taluka, district, state, pincode, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                if ($stmt->execute([$userId, $title, $description, $category, $priority, $addressLine, $village, $taluka, $district, $state, $pincode, 'uploads/issues/' . $uploadResult['filename']])) {
                    $issueId = $pdo->lastInsertId();
                    logActivity($pdo, $userId, 'issue_reported', $issueId, "Issue reported: $title");
                    
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' AND is_active = 1");
                    $admins = $stmt->fetchAll();
                    foreach ($admins as $admin) {
                        createNotification($pdo, $admin['id'], $issueId, "New issue reported by {$user['full_name']}: $title", 'assigned');
                    }
                    
                    $success = $issueId;
                } else {
                    $error = "Failed to submit issue. Please try again.";
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
    <title>Report Issue - Citizen Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        .drop-zone.has-file {
            border-color: #198754;
            background: #d1e7dd;
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
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-building"></i> Civic Issue
        </div>
        <nav>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="report-issue.php" class="active"><i class="bi bi-plus-circle"></i> Report Issue</a>
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
            <h4>Report New Issue</h4>
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
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4><i class="bi bi-check-circle"></i> Issue Submitted Successfully!</h4>
                    <p>Your issue has been registered with ID: <strong>#<?= $success ?></strong></p>
                    <p>You will be notified when the status changes.</p>
                    <a href="issue-detail.php?id=<?= $success ?>" class="btn btn-success">Track Your Issue</a>
                    <a href="report-issue.php" class="btn btn-outline-secondary">Report Another Issue</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= sanitize($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="issueForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header"><h5><i class="bi bi-info-circle"></i> Issue Details</h5></div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Issue Title *</label>
                                        <input type="text" name="title" class="form-control" required maxlength="200">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Category *</label>
                                            <select name="category" class="form-select" required>
                                                <option value="">Select Category</option>
                                                <option value="road">Road Damage</option>
                                                <option value="water">Water Supply</option>
                                                <option value="electricity">Electricity</option>
                                                <option value="sanitation">Sanitation</option>
                                                <option value="drainage">Drainage</option>
                                                <option value="street_light">Street Light</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Priority *</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="priority" id="low" value="low">
                                                <label class="btn btn-outline-info" for="low">Low</label>
                                                <input type="radio" class="btn-check" name="priority" id="medium" value="medium" checked>
                                                <label class="btn btn-outline-warning" for="medium">Medium</label>
                                                <input type="radio" class="btn-check" name="priority" id="high" value="high">
                                                <label class="btn btn-outline-danger" for="high">High</label>
                                                <input type="radio" class="btn-check" name="priority" id="critical" value="critical">
                                                <label class="btn btn-outline-dark" for="critical">Critical</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description *</label>
                                        <textarea name="description" class="form-control" rows="4" required maxlength="500" oninput="updateCharCount(this)"></textarea>
                                        <small class="text-muted"><span id="charCount">0</span>/500 characters</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header"><h5><i class="bi bi-geo-alt"></i> Location Details</h5></div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Address Line *</label>
                                        <input type="text" name="address_line" class="form-control" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Village/Area *</label>
                                            <input type="text" name="village" class="form-control" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Taluka *</label>
                                            <input type="text" name="taluka" class="form-control" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">District *</label>
                                            <input type="text" name="district" class="form-control" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">State *</label>
                                            <select name="state" class="form-select" required>
                                                <option value="">Select State</option>
                                                <?php foreach (getIndianStates() as $state): ?>
                                                    <option value="<?= $state ?>" <?= $state === 'Maharashtra' ? 'selected' : '' ?>><?= $state ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Pincode *</label>
                                            <input type="text" name="pincode" class="form-control" required pattern="[0-9]{6}" maxlength="6" placeholder="123456">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Landmark (Optional)</label>
                                        <input type="text" name="landmark" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header"><h5><i class="bi bi-camera"></i> Photo Upload *</h5></div>
                                <div class="card-body">
                                    <div class="drop-zone" id="dropZone">
                                        <i class="bi bi-cloud-upload fs-1"></i>
                                        <p class="mb-2">Drag & drop photo here or click to browse</p>
                                        <small class="text-muted">JPG, PNG only. Max 5MB</small>
                                        <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg" required hidden>
                                    </div>
                                    <img id="imagePreview" class="mt-3 w-100">
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                <i class="bi bi-send"></i> Submit Issue
                            </button>
                        </div>
                    </div>
                    
                    <div class="modal fade" id="confirmModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Submission</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to submit this issue?</p>
                                    <p class="text-muted">Once submitted, you cannot modify the details.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Confirm & Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function updateCharCount(textarea) {
            document.getElementById('charCount').textContent = textarea.value.length;
        }
        
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
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoInput.files = files;
                previewImage(files[0]);
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
                dropZone.classList.add('has-file');
            };
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>
