<?php
// Authentication and Session Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function checkAuth($requiredRole = null) {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
    
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        header('Location: ../index.php');
        exit;
    }
}

function loginUser($userId, $email, $name, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function checkSessionTimeout($timeout = 1800) {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > $timeout) {
            logoutUser();
            header('Location: ../index.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}

function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function rateLimitCheck($maxAttempts = 5, $lockoutTime = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $lockoutKey = 'login_lockout_' . $ip;
    
    if (isset($_SESSION[$lockoutKey]) && $_SESSION[$lockoutKey] > time()) {
        $remaining = $_SESSION[$lockoutKey] - time();
        return ['locked' => true, 'remaining' => $remaining];
    }
    
    $attemptKey = 'login_attempts_' . $ip;
    if (!isset($_SESSION[$attemptKey])) {
        $_SESSION[$attemptKey] = 0;
    }
    
    return ['locked' => false, 'attempts' => $_SESSION[$attemptKey]];
}

function recordFailedAttempt($maxAttempts = 5, $lockoutTime = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attemptKey = 'login_attempts_' . $ip;
    $lockoutKey = 'login_lockout_' . $ip;
    
    $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
    
    if ($_SESSION[$attemptKey] >= $maxAttempts) {
        $_SESSION[$lockoutKey] = time() + $lockoutTime;
        $_SESSION[$attemptKey] = 0;
    }
}

function clearLoginAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'];
    unset($_SESSION['login_attempts_' . $ip]);
    unset($_SESSION['login_lockout_' . $ip]);
}
