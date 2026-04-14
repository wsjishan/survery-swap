<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_guest();

if (!is_post()) {
    redirect('/login.php');
}

require_valid_csrf('/login.php');

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

$errors = [];
if ($message = validate_email($email)) {
    $errors['email'] = $message;
}
if ($message = validate_required($password, 'Password')) {
    $errors['password'] = $message;
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input(['email' => $email]);
    redirect('/login.php');
}

try {
    $stmt = db()->prepare(
        'SELECT id, name, email, password, role
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('danger', 'Invalid email or password.');
        set_old_input(['email' => $email]);
        redirect('/login.php');
    }

    login_user($user);
    set_flash('success', 'Login successful.');

    if ($user['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    }

    redirect('/surveys.php');
} catch (Throwable $e) {
    set_flash('danger', db_user_error_message($e));
    set_old_input(['email' => $email]);
    redirect('/login.php');
}
