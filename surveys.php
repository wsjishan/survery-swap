<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT s.id, s.title, s.category, s.estimated_minutes, s.reward_points, s.created_at,
            s.source_type,
            s.user_id, u.name AS posted_by,
            EXISTS(
              SELECT 1
              FROM survey_completions sc
              WHERE sc.survey_id = s.id AND sc.user_id = :viewer_id
            ) AS already_completed
     FROM surveys s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.status = :status
     ORDER BY s.created_at DESC"
);
$stmt->execute([
    'viewer_id' => $user['id'],
    'status' => SURVEY_STATUS_APPROVED,
]);
$surveys = $stmt->fetchAll();

$totalSurveys = count($surveys);
$completedByViewer = 0;
$nativeCount = 0;

foreach ($surveys as $surveyItem) {
    if ((bool) ($surveyItem['already_completed'] ?? false)) {
        $completedByViewer++;
    }

    if ((string) ($surveyItem['source_type'] ?? survey_source_type_legacy()) === survey_source_type_native()) {
        $nativeCount++;
    }
}

$pageTitle = 'Browse Surveys';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section page-head">
  <h1 class="page-title">Recent Surveys Feed</h1>
  <p class="page-subtitle">Newest published surveys first. Browse and complete to earn points.</p>
</section>

<section class="section records-kpis">
  <span class="records-kpi">Open surveys: <?= e((string) $totalSurveys) ?></span>
  <span class="records-kpi records-kpi-approved">Completed by you: <?= e((string) $completedByViewer) ?></span>
  <span class="records-kpi">Native surveys: <?= e((string) $nativeCount) ?></span>
</section>

<?php if (!$surveys): ?>
  <section class="section card card-pad">
    <p class="muted">No published surveys available right now.</p>
  </section>
<?php else: ?>
  <section class="section card card-pad survey-toolbar">
    <div class="field">
      <label class="field-label" for="survey-search">Search surveys</label>
      <input id="survey-search" type="search" class="input" placeholder="Search by title, category, or author" autocomplete="off">
    </div>
    <p class="muted small" id="survey-search-count">Showing <?= e((string) $totalSurveys) ?> of <?= e((string) $totalSurveys) ?> surveys</p>
  </section>

  <section class="section survey-feed" aria-label="Survey results feed">
    <?php foreach ($surveys as $survey): ?>
      <?php $timeText = $survey['estimated_minutes'] !== null ? e((string) $survey['estimated_minutes']) . ' min' : 'Not specified'; ?>
      <?php $postedDate = date('M j, Y', strtotime((string) $survey['created_at'])); ?>
      <?php
      $isNative = (string) ($survey['source_type'] ?? survey_source_type_legacy()) === survey_source_type_native();
      ?>
      <article
        class="survey-feed-item<?= (bool) $survey['already_completed'] ? ' is-completed' : '' ?>"
        data-survey-card
        data-search-text="<?= e(strtolower(trim($survey['title'] . ' ' . $survey['category'] . ' ' . $survey['posted_by']))) ?>"
      >
        <div class="survey-feed-rail" aria-hidden="true">
          <span class="survey-feed-score">+<?= e((string) $survey['reward_points']) ?></span>
          <span class="survey-feed-score-label">pts</span>
        </div>

        <div class="survey-feed-body">
          <p class="survey-feed-meta">
            <span>posted by <strong><?= e($survey['posted_by']) ?></strong></span>
            <span class="survey-feed-dot">&bull;</span>
            <span><?= e($postedDate) ?></span>
            <span class="survey-feed-dot">&bull;</span>
            <span><?= e($survey['category']) ?></span>
          </p>

          <h2 class="survey-feed-title">
            <a href="<?= e(url('/survey-details.php?id=' . $survey['id'])) ?>"><?= e($survey['title']) ?></a>
          </h2>

          <div class="survey-feed-tags">
            <span class="survey-feed-tag">Time: <?= $timeText ?></span>
            <span class="survey-feed-tag">Reward: +<?= e((string) $survey['reward_points']) ?> point(s)</span>
            <span class="survey-feed-tag"><?= $isNative ? 'Native' : 'Legacy' ?></span>
            <?php if ((bool) $survey['already_completed']): ?>
              <span class="survey-feed-tag survey-feed-tag-completed">Completed</span>
            <?php endif; ?>
          </div>

          <div class="actions-row survey-feed-actions">
            <a href="<?= e(url('/survey-details.php?id=' . $survey['id'])) ?>" class="btn btn-primary btn-small">
              <?= (bool) $survey['already_completed'] ? 'View Survey' : 'Answer Survey' ?>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <script>
    (function () {
      var searchInput = document.getElementById('survey-search');
      var countEl = document.getElementById('survey-search-count');
      var cards = Array.prototype.slice.call(document.querySelectorAll('[data-survey-card]'));

      if (!searchInput || !countEl || cards.length === 0) {
        return;
      }

      function updateFilter() {
        var keyword = searchInput.value.trim().toLowerCase();
        var visibleCount = 0;

        cards.forEach(function (card) {
          var haystack = (card.getAttribute('data-search-text') || '').toLowerCase();
          var visible = keyword === '' || haystack.indexOf(keyword) !== -1;
          card.hidden = !visible;
          if (visible) {
            visibleCount += 1;
          }
        });

        countEl.textContent = 'Showing ' + String(visibleCount) + ' of ' + String(cards.length) + ' surveys';
      }

      searchInput.addEventListener('input', updateFilter);
      updateFilter();
    })();
  </script>
<?php endif; ?>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
