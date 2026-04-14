<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$pdo = db();
$stmt = $pdo->query(
    "SELECT s.id, s.title, s.category, s.created_at, u.name AS submitted_by
     FROM surveys s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.status = 'pending'
     ORDER BY s.created_at ASC"
);
$pendingSurveys = $stmt->fetchAll();

$pageTitle = 'Survey Moderation';
require_once __DIR__ . '/../templates/header.php';
?>
<section class="section card card-pad">
  <div class="card-head">
    <h1 class="card-title">Admin Moderation</h1>
    <a href="<?= e(url('/admin/dashboard.php')) ?>" class="btn btn-secondary btn-small">Back to Dashboard</a>
  </div>

  <?php if (!$pendingSurveys): ?>
    <p class="muted">No pending surveys to review.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Submitted By</th>
            <th>Category</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingSurveys as $survey): ?>
            <tr>
              <td><?= e($survey['title']) ?></td>
              <td><?= e($survey['submitted_by']) ?></td>
              <td><?= e($survey['category']) ?></td>
              <td><?= e(date('M j, Y', strtotime($survey['created_at']))) ?></td>
              <td>
                <div class="actions-row">
                  <a href="<?= e(url('/survey-details.php?id=' . $survey['id'])) ?>" class="btn btn-secondary btn-small">View</a>
                  <form action="<?= e(url('/actions/approve_survey_action.php')) ?>" method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="survey_id" value="<?= e((string) $survey['id']) ?>">
                    <button type="submit" class="btn btn-success btn-small">Approve</button>
                  </form>
                  <form action="<?= e(url('/actions/reject_survey_action.php')) ?>" method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="survey_id" value="<?= e((string) $survey['id']) ?>">
                    <button type="submit" class="btn btn-danger btn-small">Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
