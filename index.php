<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$viewer = current_user();
if ($viewer) {
    if (($viewer['role'] ?? 'user') === 'admin') {
        redirect('/admin/dashboard.php');
    }

    redirect('/surveys.php');
}

$landingMode = true;
$rewardTiers = survey_reward_tiers();
$minRewardTier = min($rewardTiers);
$maxRewardTier = max($rewardTiers);
$minPublishCost = calculate_survey_total_cost($minRewardTier);
$maxPublishCost = calculate_survey_total_cost($maxRewardTier);
$pageTitle = 'Welcome';
require_once __DIR__ . '/templates/header.php';
?>
<header class="landing-topbar">
  <div class="landing-topbar-row nav-shell nav-ready">
    <a class="landing-wordmark" href="<?= e(url('/index.php')) ?>">
      <span class="landing-wordmark-mark" aria-hidden="true"></span>
      <span class="landing-wordmark-text">SURVEYSWAP</span>
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

    <nav class="main-nav" id="main-nav" data-main-nav aria-label="Landing navigation">
      <a class="nav-link" href="#how-it-works">How it Works</a>
      <a class="nav-link" href="<?= e(url('/faq.php')) ?>">FAQ</a>
    </nav>

    <div class="landing-nav-actions nav-actions">
      <a href="<?= e(url('/register.php')) ?>" class="btn btn-primary btn-small">Sign Up</a>
      <a href="<?= e(url('/login.php')) ?>" class="btn btn-ghost btn-small">Login</a>
    </div>
  </div>
</header>

<section class="landing-hero">
  <div class="landing-hero-layout">
    <div class="landing-hero-content">
      <p class="landing-kicker">Survey Exchange For Students And Researchers</p>
      <h1 class="landing-title">Get quality survey responses faster with a points-based swap.</h1>
      <p class="landing-copy">Complete published surveys to earn points, then spend those points to publish your own in-app survey. Reward tiers are 1 to 5 points, and publish cost is simply reward x <?= e((string) SURVEY_REWARD_COST_MULTIPLIER) ?>.</p>

      <ul class="landing-benefits" aria-label="Key SurveySwap benefits">
        <li class="landing-benefit-item">
          <span class="landing-benefit-icon" aria-hidden="true">1</span>
          Complete surveys - earn +<?= e((string) $minRewardTier) ?> to +<?= e((string) $maxRewardTier) ?> points instantly
        </li>
        <li class="landing-benefit-item">
          <span class="landing-benefit-icon" aria-hidden="true">2</span>
          Publish cost is fixed by reward: 1->2, 3->6, 5->10
        </li>
        <li class="landing-benefit-item">
          <span class="landing-benefit-icon" aria-hidden="true">3</span>
          Built exclusively for students and researchers
        </li>
      </ul>

      <div class="landing-actions">
        <a href="<?= e(url('/register.php')) ?>" class="btn btn-primary btn-large">Sign Up Free</a>
        <a href="<?= e(url('/login.php')) ?>" class="btn btn-ghost btn-large">Login</a>
      </div>

      <a href="#how-it-works" class="landing-scroll-cta">See how it works -&gt;</a>
    </div>

    <aside class="landing-hero-panel" aria-label="SurveySwap highlights">
      <h2 class="landing-panel-title">Research momentum, not friction</h2>
      <ul class="landing-panel-list">
        <li>Only real survey exchanges from active students and researchers</li>
        <li>Point costs make submissions fair and participation balanced</li>
        <li>No complicated verification flow before getting started</li>
      </ul>

      <dl class="landing-panel-stats">
        <div class="landing-panel-stat">
          <dt>Starter Credit</dt>
          <dd><?= STARTER_POINTS ?> points</dd>
        </div>
        <div class="landing-panel-stat">
          <dt>Per Completion</dt>
          <dd>+<?= e((string) $minRewardTier) ?> to +<?= e((string) $maxRewardTier) ?> points</dd>
        </div>
        <div class="landing-panel-stat">
          <dt>Publish Cost</dt>
          <dd><?= e((string) $minPublishCost) ?> to <?= e((string) $maxPublishCost) ?> points</dd>
        </div>
      </dl>
    </aside>
  </div>
</section>

<section class="landing-how" id="how-it-works">
  <h2 class="landing-how-title">How SurveySwap Works</h2>
  <div class="landing-how-grid">
    <article class="landing-step">
      <p class="landing-step-number">01</p>
      <h3 class="landing-step-title">Complete Surveys</h3>
      <p class="landing-step-copy">Open a survey, submit answers directly inside SurveySwap, and earn points instantly.</p>
    </article>
    <article class="landing-step">
      <p class="landing-step-number">02</p>
      <h3 class="landing-step-title">Earn Points</h3>
      <p class="landing-step-copy">Each completion gives +<?= e((string) $minRewardTier) ?> to +<?= e((string) $maxRewardTier) ?> points, and every new user starts with <?= STARTER_POINTS ?> points.</p>
    </article>
    <article class="landing-step">
      <p class="landing-step-number">03</p>
      <h3 class="landing-step-title">Publish Your Survey</h3>
      <p class="landing-step-copy">Publish cost is simple: reward x <?= e((string) SURVEY_REWARD_COST_MULTIPLIER) ?>. Example: 1 becomes 2, 3 becomes 6, and 5 becomes 10.</p>
    </article>
  </div>
</section>

<section class="landing-info-grid" aria-label="More information">
  <article class="landing-info-card" id="pricing">
    <h2>Pricing</h2>
    <p>Simple rule: publish cost = reward x <?= e((string) SURVEY_REWARD_COST_MULTIPLIER) ?>. Current range is <?= e((string) $minPublishCost) ?> to <?= e((string) $maxPublishCost) ?> points.</p>
  </article>
  <article class="landing-info-card" id="blog">
    <h2>Guides</h2>
    <p>Get simple guidance to write better questions, reach the right audience, and improve response quality.</p>
  </article>
  <article class="landing-info-card">
    <h2>FAQ</h2>
    <p>Find quick answers about points, publication rules, and survey flow.</p>
    <p><a href="<?= e(url('/faq.php')) ?>">Read the full FAQ</a></p>
  </article>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
