<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user has specific role
function hasRole($requiredRole) {
    return isLoggedIn() && $_SESSION['role'] === $requiredRole;
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /unauthorized.php');
        exit();
    }
}

// Require any of the specified roles
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header('Location: /unauthorized.php');
        exit();
    }
}

// Log activity
function logActivity($conn, $action, $tableName = null, $recordId = null, $details = null) {
    $userId = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $userId, $action, $tableName, $recordId, $details);
    $stmt->execute();
    $stmt->close();
}

// Get current user info
function getCurrentUser($conn) {
    if (!isLoggedIn()) return null;
    
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}
?>
