<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_guest();

if (!is_post()) {
    redirect('/register.php');
}

require_valid_csrf('/register.php');

$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

$errors = [];

if ($message = validate_required($name, 'Name')) {
    $errors['name'] = $message;
}

if ($message = validate_email($email)) {
    $errors['email'] = $message;
}

if ($message = validate_password($password, 6)) {
    $errors['password'] = $message;
}

if ($confirmPassword === '') {
    $errors['confirm_password'] = 'Confirm password is required.';
} elseif ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Password confirmation does not match.';
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input([
        'name' => $name,
        'email' => $email,
    ]);
    redirect('/register.php');
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        set_validation_errors([
            'email' => 'This email is already registered.',
        ]);
        set_old_input([
            'name' => $name,
            'email' => $email,
        ]);
        redirect('/register.php');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, role, points)
         VALUES (:name, :email, :password, :role, :points)'
    );

    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'points' => STARTER_POINTS,
    ]);

    $userId = (int) $pdo->lastInsertId();

    record_point_transaction(
        $pdo,
        $userId,
        TX_TYPE_CREDIT,
        STARTER_POINTS,
        TX_REASON_STARTER,
        null
    );

    $pdo->commit();

    login_user([
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => 'user',
    ]);

    set_flash('success', 'Registration successful. Welcome to SurveySwap!');
    redirect('/surveys.php');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('danger', db_user_error_message($e));
    set_old_input([
        'name' => $name,
        'email' => $email,
    ]);
    redirect('/register.php');
}
