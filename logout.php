<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    require_valid_csrf('/surveys.php');
}

logout_user();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

set_flash('success', 'You have been logged out successfully.');
redirect('/login.php');
