<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

if (is_admin()) {
    redirect('/admin/dashboard.php');
}

redirect('/surveys.php');
