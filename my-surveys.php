<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT s.id, s.title, s.status, s.category, s.created_at
     FROM surveys s
     WHERE s.user_id = :user_id
     ORDER BY s.created_at DESC'
);
$stmt->execute(['user_id' => $user['id']]);
$surveys = $stmt->fetchAll();

$totalSurveys = count($surveys);
$pendingSurveys = 0;
$approvedSurveys = 0;
$rejectedSurveys = 0;

foreach ($surveys as $survey) {
  $status = (string) ($survey['status'] ?? SURVEY_STATUS_PENDING);
  if ($status === SURVEY_STATUS_APPROVED) {
    $approvedSurveys++;
  } elseif ($status === SURVEY_STATUS_REJECTED) {
    $rejectedSurveys++;
  } else {
    $pendingSurveys++;
  }
}

$pageTitle = 'My Surveys';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section records-shell card card-pad">
  <div class="records-head">
    <div>
      <h1 class="records-title">My Surveys</h1>
      <p class="records-subtitle">Track your submitted surveys, moderation status, and activity.</p>
    </div>
    <a href="<?= e(url('/submit-survey.php')) ?>" class="btn btn-primary btn-small">Submit New</a>
  </div>

  <div class="records-kpis" aria-label="Survey summary">
    <span class="records-kpi">Total: <?= e((string) $totalSurveys) ?></span>
    <span class="records-kpi records-kpi-pending">Pending: <?= e((string) $pendingSurveys) ?></span>
    <span class="records-kpi records-kpi-approved">Approved: <?= e((string) $approvedSurveys) ?></span>
    <span class="records-kpi records-kpi-rejected">Rejected: <?= e((string) $rejectedSurveys) ?></span>
  </div>

  <?php if (!$surveys): ?>
    <p class="muted records-empty">You have not submitted any surveys yet.</p>
  <?php else: ?>
    <div class="table-wrap records-table-wrap">
      <table class="data-table records-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Category</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($surveys as $survey): ?>
            <?php
            $status = (string) $survey['status'];
            $statusClass = match ($status) {
                SURVEY_STATUS_APPROVED => 'badge-approved',
                SURVEY_STATUS_REJECTED => 'badge-rejected',
                default => 'badge-pending',
            };
            ?>
            <tr>
              <td class="records-title-cell"><?= e($survey['title']) ?></td>
              <td><span class="badge <?= e($statusClass) ?>"><?= e($status) ?></span></td>
              <td><?= e($survey['category']) ?></td>
              <td><?= e(date('M j, Y', strtotime($survey['created_at']))) ?></td>
              <td><a href="<?= e(url('/survey-details.php?id=' . $survey['id'])) ?>" class="btn btn-secondary btn-small">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
