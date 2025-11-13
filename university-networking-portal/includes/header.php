<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

start_session();
$user = current_user();
$pageTitle = $pageTitle ?? 'University Networking Portal';

$navLinks = [];

if ($user) {
    switch ($user['role']) {
        case 'admin':
            $navLinks = [
                ['label' => 'Dashboard', 'href' => '/public/admin/dashboard.php'],
                ['label' => 'Users', 'href' => '/public/admin/users.php'],
                ['label' => 'Clubs', 'href' => '/public/admin/clubs.php'],
                ['label' => 'Opportunities', 'href' => '/public/admin/opportunities.php'],
                ['label' => 'Events', 'href' => '/public/admin/events.php'],
                ['label' => 'Alumni', 'href' => '/public/admin/alumni.php'],
            ];
            break;
        case 'moderator':
            $navLinks = [
                ['label' => 'Dashboard', 'href' => '/public/moderator/dashboard.php'],
                ['label' => 'Manage Events', 'href' => '/public/moderator/events.php'],
            ];
            break;
        case 'student':
            $navLinks = [
                ['label' => 'Dashboard', 'href' => '/public/student/dashboard.php'],
                ['label' => 'Opportunities', 'href' => '/public/student/opportunities.php'],
                ['label' => 'Events', 'href' => '/public/student/events.php'],
                ['label' => 'Alumni Directory', 'href' => '/public/student/alumni.php'],
                ['label' => 'My Profile', 'href' => '/public/student/profile.php'],
            ];
            break;
    }
}

$flashMessages = get_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="role-<?= $user['role'] ?? 'guest' ?>">
    <nav class="navbar navbar-expand-lg shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-uppercase" href="<?= $user ? '/public/' . $user['role'] . '/dashboard.php' : '/public/login.php' ?>">
                UNP Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($navLinks as $link): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $link['href'] ?>"><?= htmlentities($link['label']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex">
                    <?php if ($user): ?>
                        <span class="navbar-text me-3">
                            <?= htmlentities(ucfirst($user['role'])) ?>: <?= htmlentities($user['name']) ?>
                        </span>
                        <a class="btn btn-outline-light" href="/public/logout.php">Sign Out</a>
                    <?php else: ?>
                        <a class="btn btn-primary" href="/public/login.php">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php if (!empty($flashMessages)): ?>
            <div class="flash-wrapper mb-3">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= htmlentities($type) ?> shadow-sm">
                            <?= htmlentities($message) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
