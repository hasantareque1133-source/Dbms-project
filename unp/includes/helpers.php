<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Ensure the PHP session is active with secure defaults.
 */
function ensure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name(SESSION_COOKIE_NAME);
    session_start();
}

/**
 * Determine whether the current request is a POST.
 */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Redirect to a given path relative to BASE_URL.
 */
function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Generate a CSRF token and store it in the session.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();

    return $token;
}

/**
 * Validate a CSRF token.
 */
function verify_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($token) || empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }

    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    $isFresh = (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_TTL;

    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);

    return $isValid && $isFresh;
}

/**
 * Escape HTML entities.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Store a flash message for the next request.
 */
function set_flash(string $type, string $message): void
{
    ensure_session();
    $_SESSION['flashes'][$type][] = $message;
}

/**
 * Retrieve and clear flash messages.
 *
 * @return array<string, string[]>
 */
function get_flashes(): array
{
    ensure_session();
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

/**
 * Retrieve a user-friendly label for an opportunity type.
 */
function opportunity_label(string $type): string
{
    return match ($type) {
        'job' => 'Job Opportunity',
        'internship' => 'Internship',
        'research' => 'Research Opening',
        default => ucfirst($type),
    };
}
