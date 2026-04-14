<?php

declare(strict_types=1);

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION['user']['id'] : null;
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    static $cachedUser = null;

    if ($cachedUser !== null) {
        return $cachedUser;
    }

    try {
        $stmt = db()->prepare('SELECT id, name, email, role, points, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => current_user_id()]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        logout_user();
        return null;
    }

    if (!$user) {
        logout_user();
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in() || !current_user()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('/login.php');
    }
}

function require_guest(): void
{
    if (!is_logged_in()) {
        return;
    }

    $user = current_user();
    if (!$user) {
        // current_user() already clears broken sessions.
        return;
    }

    if (($user['role'] ?? 'user') === 'admin') {
        redirect('/admin/dashboard.php');
    }

    redirect('/surveys.php');
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        set_flash('danger', 'You are not authorized to access that page.');
        redirect('/surveys.php');
    }
}
