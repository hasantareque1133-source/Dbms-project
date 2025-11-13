<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Attempt to authenticate a user by email and password.
 */
function authenticate(string $email, string $password): bool
{
    ensure_session();

    $sql = 'SELECT u.id, u.name, u.email, u.department, u.academic_year, u.password_hash, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.email = :email LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'department' => $user['department'],
        'academic_year' => $user['academic_year'],
        'role' => $user['role_name'],
    ];

    log_activity((int)$user['id'], 'login', 'User logged in');

    return true;
}

/**
 * Destroy the current session.
 */
function logout(): void
{
    ensure_session();

    if (!empty($_SESSION['user']['id'])) {
        log_activity((int)$_SESSION['user']['id'], 'logout', 'User logged out');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

/**
 * Require the user to be authenticated, redirecting to login if not.
 */
function require_auth(): void
{
    ensure_session();
    if (empty($_SESSION['user'])) {
        redirect('/login.php');
    }
}

/**
 * Require that the authenticated user has one of the specified roles.
 */
function require_role(string ...$roles): void
{
    require_auth();

    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

/**
 * Retrieve the currently authenticated user (if any).
 *
 * @return array{id:int,name:string,email:string,department:?string,academic_year:?string,role:string}|null
 */
function current_user(): ?array
{
    ensure_session();
    return $_SESSION['user'] ?? null;
}

/**
 * Determine whether the current user has the given role.
 */
function is_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

/**
 * Persist an activity log entry.
 */
function log_activity(int $userId, string $action, string $details = ''): void
{
    $sql = 'INSERT INTO activity_log (user_id, action, details) VALUES (:user_id, :action, :details)';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
    ]);
}
