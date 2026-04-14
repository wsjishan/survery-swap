<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT s.id, s.title, s.reward_points, sc.completed_at
     FROM survey_completions sc
     INNER JOIN surveys s ON s.id = sc.survey_id
     WHERE sc.user_id = :user_id
     ORDER BY sc.completed_at DESC'
);
$stmt->execute(['user_id' => $user['id']]);
$completions = $stmt->fetchAll();

$totalCompleted = count($completions);
$totalEarned = 0;

foreach ($completions as $completion) {
  $totalEarned += (int) ($completion['reward_points'] ?? 0);
}

$pageTitle = 'Completed Surveys';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section records-shell card card-pad">
  <div class="records-head">
    <div>
      <h1 class="records-title">Completed Surveys</h1>
      <p class="records-subtitle">See your completed responses and rewards earned over time.</p>
    </div>
  </div>

  <div class="records-kpis" aria-label="Completion summary">
    <span class="records-kpi">Completed: <?= e((string) $totalCompleted) ?></span>
    <span class="records-kpi records-kpi-approved">Points Earned: +<?= e((string) $totalEarned) ?></span>
  </div>

  <?php if (!$completions): ?>
    <p class="muted records-empty">You have not completed any surveys yet.</p>
  <?php else: ?>
    <div class="table-wrap records-table-wrap">
      <table class="data-table records-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Reward</th>
            <th>Completed On</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($completions as $completion): ?>
            <tr>
              <td class="records-title-cell"><?= e($completion['title']) ?></td>
              <td><span class="records-reward">+<?= e((string) $completion['reward_points']) ?> point(s)</span></td>
              <td><?= e(date('M j, Y g:i A', strtotime($completion['completed_at']))) ?></td>
              <td><a href="<?= e(url('/survey-details.php?id=' . $completion['id'])) ?>" class="btn btn-secondary btn-small">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
