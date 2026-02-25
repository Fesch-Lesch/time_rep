<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getBasePath() {
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script);
    
    if (preg_match('#/(admin|teacher|public)$#', $dir)) {
        return '..';
    }
    return '.';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $base = getBasePath();
        header("Location: $base/login.php");
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['role'], $roles)) {
        $base = getBasePath();
        header("Location: $base/dashboard.php?error=access_denied");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function isCaptain() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'captain';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function setCurrentUserForTriggers($pdo) {
    $userId = getCurrentUserId();
    $username = getCurrentUsername();
    $pdo->exec("SET @current_user_id = " . ($userId ? (int)$userId : 'NULL'));
    $pdo->exec("SET @current_username = " . ($username ? "'" . addslashes($username) . "'" : 'NULL'));
}
?>