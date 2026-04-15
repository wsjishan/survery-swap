<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FAQ';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section page-head">
  <h1 class="page-title">Frequently Asked Questions</h1>
  <p class="page-subtitle">Quick answers about points, survey publishing, and who SurveySwap is for.</p>
</section>

<section class="section card card-pad">
  <ul class="landing-faq-list" aria-label="Frequently asked questions">
    <li class="landing-faq-item">
      <p class="landing-faq-question">How do I earn points?</p>
      <p class="landing-faq-answer">Complete published surveys and receive points after each valid submission.</p>
    </li>
    <li class="landing-faq-item">
      <p class="landing-faq-question">Do new users get starter points?</p>
      <p class="landing-faq-answer">Yes. Every new account starts with <?= STARTER_POINTS ?> points.</p>
    </li>
    <li class="landing-faq-item">
      <p class="landing-faq-question">How many points does publishing cost?</p>
      <p class="landing-faq-answer">Publishing cost is reward x 2. For example: reward 1 = cost 2, reward 3 = cost 6, reward 5 = cost 10.</p>
    </li>
    <li class="landing-faq-item">
      <p class="landing-faq-question">Who is SurveySwap for?</p>
      <p class="landing-faq-answer">It is built for students and researchers who need survey responses faster.</p>
    </li>
  </ul>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
