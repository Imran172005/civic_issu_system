<?php
// Shared helper functions

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'secondary',
        'assigned' => 'primary',
        'in_progress' => 'warning',
        'resolved' => 'success',
        'closed' => 'dark'
    ];
    return $colors[$status] ?? 'secondary';
}

function getPriorityColor($priority) {
    $colors = [
        'low' => 'info',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'bg-danger'
    ];
    return $colors[$priority] ?? 'info';
}

function getCategoryLabel($category) {
    $labels = [
        'road' => 'Road Damage',
        'water' => 'Water Supply',
        'electricity' => 'Electricity',
        'sanitation' => 'Sanitation',
        'drainage' => 'Drainage',
        'street_light' => 'Street Light',
        'other' => 'Other'
    ];
    return $labels[$category] ?? $category;
}

function getCategoryIcon($category) {
    $icons = [
        'road' => 'bi-road',
        'water' => 'bi-droplet',
        'electricity' => 'bi-lightning',
        'sanitation' => 'bi-trash',
        'drainage' => 'bi-water',
        'street_light' => 'bi-lightbulb',
        'other' => 'bi-three-dots'
    ];
    return $icons[$category] ?? 'bi-circle';
}

function formatDate($date, $format = 'd M Y, h:i A') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed'
    ];
    return $labels[$status] ?? $status;
}

function getPriorityLabel($priority) {
    $labels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical'
    ];
    return $labels[$priority] ?? $priority;
}

function getIndianStates() {
    return [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi', 'Jammu and Kashmir',
        'Ladakh', 'Puducherry', 'Chandigarh', 'Dadra and Nagar Haveli',
        'Daman and Diu', 'Lakshadweep', 'Andaman and Nicobar Islands'
    ];
}

function uploadFile($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG and PNG are allowed.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('issue_') . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => $destination];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file.'];
}

function createNotification($pdo, $userId, $issueId, $message, $type) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, issue_id, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $issueId, $message, $type]);
}

function logActivity($pdo, $userId, $action, $issueId = null, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, issue_id, details) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $action, $issueId, $details]);
}

function getUnreadNotificationsCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
