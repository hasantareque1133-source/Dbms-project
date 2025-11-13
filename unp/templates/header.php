<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

ensure_session();
$user = current_user();
$title = isset($pageTitle) ? e($pageTitle . ' | ' . APP_NAME) : e(APP_NAME);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL; ?>/dashboard.php">
                <span class="brand-accent">UNP</span> Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <?php if ($user): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <?php if ($user['role'] === 'Admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/admin/users.php">Users</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/admin/opportunities.php">Opportunities</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/admin/events.php">Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/admin/alumni.php">Alumni</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/admin/activity.php">Activity</a></li>
                        <?php elseif ($user['role'] === 'Student'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/student/opportunities.php">Opportunities</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/student/events.php">Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/student/alumni.php">Alumni Directory</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/student/profile.php">Profile</a></li>
                        <?php elseif ($user['role'] === 'Moderator'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/moderator/events.php">Manage Events</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/student/alumni.php">Alumni Directory</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="d-flex align-items-center">
                        <div class="text-end me-3 text-light small">
                            <div class="fw-semibold"><?= e($user['name']); ?></div>
                            <div><?= e($user['role']); ?></div>
                        </div>
                        <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL; ?>/logout.php">Sign out</a>
                    </div>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL; ?>/login.php">Log In</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container-fluid py-4">
