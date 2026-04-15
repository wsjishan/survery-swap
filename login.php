<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = pull_validation_errors();

$authMode = true;
$pageTitle = 'Login';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section auth-shell auth-shell-single card card-pad">
  <header class="page-head">
    <p class="auth-brand">Sign in</p>
    <h1 class="page-title">Welcome back</h1>
    <p class="page-subtitle">Log in to continue exchanging surveys.</p>
  </header>

  <form action="<?= e(url('/actions/login_action.php')) ?>" method="post" novalidate class="form-grid">
    <?= csrf_field() ?>

    <div class="field">
      <label class="field-label" for="email">Email</label>
      <input id="email" name="email" type="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required>
      <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label class="field-label" for="password">Password</label>
      <input id="password" name="password" type="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
      <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Login</button>
    </div>
  </form>

  <p class="auth-switch">No account yet? <a href="<?= e(url('/register.php')) ?>">Register</a></p>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
