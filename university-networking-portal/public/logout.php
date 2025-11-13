<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

start_session();
session_unset();
session_destroy();

set_flash('success', 'You have been signed out successfully.');
redirect('/public/login.php');

