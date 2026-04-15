<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_guest();

$errors = pull_validation_errors();

$authMode = true;
$pageTitle = 'Register';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section auth-shell card card-pad">
  <div class="auth-frame">
    <aside class="auth-spotlight" aria-label="What you get">
      <p class="auth-spotlight-kicker">Get Started</p>
      <h2 class="auth-spotlight-title">Create your account and start with <?= STARTER_POINTS ?> free points.</h2>
      <p class="auth-spotlight-copy">Use points to publish your own surveys and earn more by completing surveys from other students and researchers.</p>

      <ul class="auth-spotlight-list">
        <li>Native in-app survey responses</li>
        <li>Simple point-based reward flow</li>
        <li>Fast campaign launch with flexible targets</li>
      </ul>
    </aside>

    <div class="auth-shell card card-pad auth-shell-refined">
      <header class="page-head">
        <p class="auth-brand">Sign up</p>
        <h1 class="page-title">Create your account</h1>
        <p class="page-subtitle">New users receive <?= STARTER_POINTS ?> starter points.</p>
      </header>

      <form action="<?= e(url('/actions/register_action.php')) ?>" method="post" data-form="register" novalidate class="form-grid">
        <?= csrf_field() ?>

        <div class="field">
          <label class="field-label" for="name">Full Name</label>
          <input id="name" name="name" type="text" class="input <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e(old('name')) ?>" required>
          <?php if (isset($errors['name'])): ?><p class="error-text"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label class="field-label" for="email">Email</label>
          <input id="email" name="email" type="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e(old('email')) ?>" required>
          <?php if (isset($errors['email'])): ?><p class="error-text"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="field form-grid form-grid-2">
          <div class="field">
            <label class="field-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
            <?php if (isset($errors['password'])): ?><p class="error-text"><?= e($errors['password']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="field-label" for="confirm_password">Confirm Password</label>
            <input id="confirm_password" name="confirm_password" type="password" class="input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
            <?php if (isset($errors['confirm_password'])): ?><p class="error-text"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
      </form>

      <p class="auth-switch">Already have an account? <a href="<?= e(url('/login.php')) ?>">Login</a></p>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
