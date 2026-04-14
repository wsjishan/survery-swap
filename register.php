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
  <header class="page-head">
    <p class="auth-brand">SurveySwap</p>
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
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
