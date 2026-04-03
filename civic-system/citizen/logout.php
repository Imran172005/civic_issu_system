<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth('citizen');

logActivity($pdo, $_SESSION['user_id'], 'logout');

logoutUser();

header('Location: ../index.php?loggedout=1');
exit;