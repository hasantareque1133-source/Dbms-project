<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    $user = current_user();
    redirect('/public/' . $user['role'] . '/dashboard.php');
}

redirect('/public/login.php');

