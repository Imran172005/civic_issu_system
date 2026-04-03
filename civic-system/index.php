<?php
// Main index - redirects to appropriate login based on role or shows selection
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

checkSessionTimeout();

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'citizen':
            header('Location: citizen/dashboard.php');
            break;
        case 'solver':
            header('Location: solver/dashboard.php');
            break;
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        default:
            logoutUser();
            header('Location: index.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civic Issue Reporting System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #6f42c1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .portal-select {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .portal-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .portal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .portal-btn.citizen { background: #0d6efd; }
        .portal-btn.solver { background: #198754; }
        .portal-btn.admin { background: #6f42c1; }
        .logo-area {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-area i {
            font-size: 3rem;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-area mb-4">
            <i class="bi bi-building"></i>
            <h2 class="text-white mt-2">Rural Civic Issue Reporting</h2>
        </div>
        <div class="portal-select">
            <button class="portal-btn citizen" onclick="location.href='citizen/login.php'">
                <i class="bi bi-person"></i><br>Citizen
            </button>
            <button class="portal-btn solver" onclick="location.href='solver/login.php'">
                <i class="bi bi-tools"></i><br>Solver
            </button>
            <button class="portal-btn admin" onclick="location.href='admin/login.php'">
                <i class="bi bi-shield-check"></i><br>Admin
            </button>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">Session expired. Please login again.</div>
                <?php endif; ?>
                <?php if (isset($_GET['loggedout'])): ?>
                    <div class="alert alert-success">You have been logged out successfully.</div>
                <?php endif; ?>
                <h4 class="text-center mb-4">Select a Portal</h4>
                <p class="text-muted text-center">Choose your portal above to login or register as a citizen.</p>
            </div>
        </div>
    </div>
</body>
</html>
