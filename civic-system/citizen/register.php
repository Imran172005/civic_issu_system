<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkSessionTimeout();

if (isLoggedIn() && $_SESSION['role'] === 'citizen') {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $address = sanitize($_POST['address']);
        $city = sanitize($_POST['city']);
        $state = sanitize($_POST['state']);
        
        if ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $profilePic = null;
                
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
                
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, address, city, profile_pic) VALUES (?, ?, ?, ?, 'citizen', ?, ?, ?)");
                if ($stmt->execute([$fullName, $email, $phone, $hashedPassword, $address, $city, $profilePic])) {
                    $success = "Registration successful! Please login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Registration - Civic Issue System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .register-card {
            max-width: 600px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .brand-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        .brand-icon i {
            font-size: 2rem;
            color: white;
        }
        .password-strength {
            height: 5px;
            transition: width 0.3s, background-color 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="register-card bg-white">
                    <div class="card-body p-4">
                        <div class="text-center">
                            <div class="brand-icon">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h3 class="text-primary">Citizen Registration</h3>
                            <p class="text-muted">Create an account to report civic issues</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?= sanitize($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?= sanitize($success) ?>
                                <a href="login.php" class="alert-link">Click here to login</a>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone *</label>
                                    <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10,}">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profile Picture (Optional)</label>
                                    <input type="file" name="profile_pic" class="form-control" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6" oninput="checkPasswordStrength(this.value)">
                                    <div class="password-strength bg-secondary" id="strengthBar"></div>
                                    <small class="text-muted">At least 6 characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <h5 class="mb-3">Address Details</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Address *</label>
                                <textarea name="address" class="form-control" rows="2" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City *</label>
                                    <input type="text" name="city" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State *</label>
                                    <select name="state" class="form-select" required>
                                        <option value="">Select State</option>
                                        <?php foreach (getIndianStates() as $state): ?>
                                            <option value="<?= $state ?>"><?= $state ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-person-plus"></i> Register
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <p class="text-center mb-0">
                            Already registered? <a href="login.php" class="text-primary fw-bold">Login here</a>
                        </p>
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="text-muted small">
                                <i class="bi bi-arrow-left"></i> Back to Portal Selection
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength(password) {
            const bar = document.getElementById('strengthBar');
            let strength = 0;
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            bar.style.width = strength + '%';
            if (strength <= 25) bar.className = 'password-strength bg-danger';
            else if (strength <= 50) bar.className = 'password-strength bg-warning';
            else if (strength <= 75) bar.className = 'password-strength bg-info';
            else bar.className = 'password-strength bg-success';
        }
    </script>
</body>
</html>
