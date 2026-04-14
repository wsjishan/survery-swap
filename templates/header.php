<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$flashes = pull_flashes();
$user = current_user();
$isLandingMode = isset($landingMode) && $landingMode === true;
$isAuthMode = isset($authMode) && $authMode === true;
$brandHref = '/index.php';
if ($user) {
    $brandHref = ((string) ($user['role'] ?? 'user') === 'admin')
        ? '/admin/dashboard.php'
        : '/surveys.php';
}
$currentPath = basename($_SERVER['PHP_SELF'] ?? '');
$isAdminPath = str_contains((string) ($_SERVER['PHP_SELF'] ?? ''), '/admin/');
$showHeader = !$isLandingMode && !$isAuthMode;
$bodyClass = trim(
    ($isLandingMode ? 'landing-mode ' : '')
    . ($isAuthMode ? 'auth-mode' : '')
);
$mainClass = trim(
    'site-main'
    . ($isLandingMode ? ' landing-main' : '')
    . ($isAuthMode ? ' auth-main' : '')
);
$containerClass = $isLandingMode
    ? 'landing-shell'
    : ($isAuthMode ? 'auth-layout' : 'container');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($showHeader): ?>
  <header class="site-header">
    <div class="container nav-shell">
      <a class="brand" href="<?= e(url($brandHref)) ?>">
        <span class="brand-mark" aria-hidden="true"></span>
        <span class="brand-label">SurveySwap</span>
      </a>

      <button
        class="nav-toggle"
        type="button"
        data-nav-toggle
        aria-controls="main-nav"
        aria-expanded="false"
        aria-label="Toggle navigation menu"
      >
        <span class="nav-toggle-bar" aria-hidden="true"></span>
        <span class="nav-toggle-bar" aria-hidden="true"></span>
        <span class="nav-toggle-bar" aria-hidden="true"></span>
      </button>

      <nav class="main-nav" id="main-nav" data-main-nav aria-label="Main navigation">
        <?php if ($user): ?>
          <a class="nav-link <?= $currentPath === 'surveys.php' ? 'active' : '' ?>" href="<?= e(url('/surveys.php')) ?>">Browse Surveys</a>
          <a class="nav-link <?= $currentPath === 'submit-survey.php' ? 'active' : '' ?>" href="<?= e(url('/submit-survey.php')) ?>">Submit Survey</a>
          <a class="nav-link <?= $currentPath === 'my-surveys.php' ? 'active' : '' ?>" href="<?= e(url('/my-surveys.php')) ?>">My Surveys</a>
          <a class="nav-link <?= $currentPath === 'completed-surveys.php' ? 'active' : '' ?>" href="<?= e(url('/completed-surveys.php')) ?>">Completed</a>
          <?php if (is_admin()): ?>
            <a class="nav-link <?= $isAdminPath ? 'active' : '' ?>" href="<?= e(url('/admin/dashboard.php')) ?>">Admin</a>
          <?php endif; ?>
        <?php else: ?>
          <a class="nav-link <?= $currentPath === 'index.php' ? 'active' : '' ?>" href="<?= e(url('/index.php')) ?>">Home</a>
          <a class="nav-link <?= $currentPath === 'surveys.php' ? 'active' : '' ?>" href="<?= e(url('/surveys.php')) ?>">Browse Surveys</a>
        <?php endif; ?>
      </nav>

      <div class="nav-actions">
        <?php if ($user): ?>
          <div class="account-chip">
            <span class="account-name"><?= e($user['name']) ?></span>
            <span class="account-divider" aria-hidden="true"></span>
            <span class="account-points"><?= e((string) $user['points']) ?> pts</span>
          </div>
          <form action="<?= e(url('/logout.php')) ?>" method="post" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-small nav-logout">Logout</button>
          </form>
        <?php else: ?>
          <a href="<?= e(url('/login.php')) ?>" class="btn btn-secondary btn-small">Login</a>
          <a href="<?= e(url('/register.php')) ?>" class="btn btn-primary btn-small">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
<?php endif; ?>

<main class="<?= e($mainClass) ?>">
  <div class="<?= e($containerClass) ?>">
    <?php foreach ($flashes as $flash): ?>
      <?php
      $flashType = match ((string) ($flash['type'] ?? 'info')) {
          'success' => 'success',
          'danger' => 'danger',
          'warning' => 'warning',
          default => 'info',
      };
      ?>
      <div class="flash flash-<?= e($flashType) ?>">
        <?= e($flash['message']) ?>
      </div>
    <?php endforeach; ?>
