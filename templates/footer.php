<?php

declare(strict_types=1);

clear_old_input();
$isLandingMode = isset($landingMode) && $landingMode === true;
?>
  </div>
</main>

<?php if (!$isLandingMode): ?>
<footer class="site-footer">
  <div class="container footer-row">
    <p class="footer-copy">&copy; <?= date('Y') ?> SurveySwap</p>
    <p class="footer-note">Trusted survey exchange for students and researchers.</p>
  </div>
</footer>
<?php endif; ?>

<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
