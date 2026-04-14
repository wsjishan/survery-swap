<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT s.id, s.title, s.category, s.estimated_minutes, s.reward_points, s.created_at,
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

$pageTitle = 'Browse Surveys';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section page-head">
  <h1 class="page-title">Recent Surveys Feed</h1>
  <p class="page-subtitle">Newest published surveys first. Browse and complete to earn points.</p>
</section>

<?php if (!$surveys): ?>
  <section class="section card card-pad">
    <p class="muted">No published surveys available right now.</p>
  </section>
<?php else: ?>
  <section class="section survey-feed" aria-label="Survey results feed">
    <?php foreach ($surveys as $survey): ?>
      <?php $timeText = $survey['estimated_minutes'] !== null ? e((string) $survey['estimated_minutes']) . ' min' : 'Not specified'; ?>
      <?php $postedDate = date('M j, Y', strtotime((string) $survey['created_at'])); ?>
      <article class="survey-feed-item<?= (bool) $survey['already_completed'] ? ' is-completed' : '' ?>">
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
            <?php if ((bool) $survey['already_completed']): ?>
              <span class="survey-feed-tag survey-feed-tag-completed">Completed</span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
