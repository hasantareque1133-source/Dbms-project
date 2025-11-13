<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'moderator':
            header('Location: /moderator/dashboard.php');
            break;
        case 'student':
            header('Location: /student/dashboard.php');
            break;
        default:
            header('Location: /login.php');
    }
    exit();
} else {
    header('Location: /login.php');
    exit();
}
?>
