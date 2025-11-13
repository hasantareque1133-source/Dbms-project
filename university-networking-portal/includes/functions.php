<?php

declare(strict_types=1);

/**
 * Miscellaneous helper functions shared across the portal.
 */

if (!function_exists('start_session')) {
    function start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        start_session();
        $_SESSION['flash'][$type][] = $message;
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): array
    {
        start_session();
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        start_session();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return current_user() !== null;
    }
}

if (!function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        $user = current_user();
        if (!$user) {
            return false;
        }
        return in_array($user['role'], $roles, true);
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            set_flash('warning', 'Please sign in to continue.');
            redirect('/public/login.php');
        }
    }
}

if (!function_exists('require_role')) {
    function require_role(string ...$roles): void
    {
        require_login();
        if (!has_role(...$roles)) {
            set_flash('danger', 'You do not have permission to access that resource.');
            redirect('/public/login.php');
        }
    }
}

