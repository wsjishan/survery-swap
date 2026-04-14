<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pdo = db();

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalSurveys = (int) $pdo->query('SELECT COUNT(*) FROM surveys')->fetchColumn();
$totalActive = (int) $pdo->query("SELECT COUNT(*) FROM surveys WHERE status = 'active'")->fetchColumn();
$totalPending = (int) $pdo->query("SELECT COUNT(*) FROM surveys WHERE status = 'pending'")->fetchColumn();
$totalCompletions = (int) $pdo->query('SELECT COUNT(*) FROM survey_completions')->fetchColumn();

$stmtPending = $pdo->query(
    "SELECT s.id, s.title, s.category, s.created_at, u.name AS submitted_by
     FROM surveys s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.status = 'pending'
     ORDER BY s.created_at ASC
     LIMIT 8"
);
$pendingSurveys = $stmtPending->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../templates/header.php';
?>
<section class="section page-head">
  <h1 class="page-title">Admin Dashboard</h1>
  <p class="page-subtitle">Moderation and platform overview.</p>
</section>

<section class="section grid grid-4">
  <article class="card card-pad"><p class="metric-label">Users</p><p class="metric-value"><?= e((string) $totalUsers) ?></p></article>
  <article class="card card-pad"><p class="metric-label">Surveys</p><p class="metric-value"><?= e((string) $totalSurveys) ?></p></article>
  <article class="card card-pad"><p class="metric-label">Active</p><p class="metric-value"><?= e((string) $totalActive) ?></p></article>
  <article class="card card-pad"><p class="metric-label">Pending</p><p class="metric-value"><?= e((string) $totalPending) ?></p></article>
</section>

<section class="section card card-pad">
  <div class="card-head">
    <h2 class="card-title">Moderation Queue</h2>
    <a class="btn btn-primary btn-small" href="<?= e(url('/admin/moderation.php')) ?>">Open Moderation</a>
  </div>

  <p class="muted small section-space">Total completions across platform: <strong><?= e((string) $totalCompletions) ?></strong></p>

  <?php if (!$pendingSurveys): ?>
    <p class="muted">No pending surveys right now.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Submitted By</th>
            <th>Category</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingSurveys as $survey): ?>
            <tr>
              <td><?= e($survey['title']) ?></td>
              <td><?= e($survey['submitted_by']) ?></td>
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
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
