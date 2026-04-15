<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$surveyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($surveyId < 1) {
    set_flash('danger', 'Invalid survey selected.');
    redirect('/surveys.php');
}

$user = current_user();
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT s.*, u.name AS posted_by
     FROM surveys s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    set_flash('danger', 'Survey not found.');
    redirect('/surveys.php');
}

$isOwner = (int) $survey['user_id'] === (int) $user['id'];
$isAdmin = is_admin();
$canCompleteOwnSurvey = $isOwner && $isAdmin;
$isAllowedByStatus = $survey['status'] === SURVEY_STATUS_APPROVED || $isOwner || $isAdmin;

if (!$isAllowedByStatus) {
    set_flash('danger', 'You are not allowed to view this survey.');
    redirect('/surveys.php');
}

$stmtCompleted = $pdo->prepare(
    'SELECT id
     FROM survey_completions
     WHERE survey_id = :survey_id AND user_id = :user_id
     LIMIT 1'
);
$stmtCompleted->execute([
    'survey_id' => $surveyId,
    'user_id' => $user['id'],
]);
$alreadyCompleted = (bool) $stmtCompleted->fetchColumn();
$submittedAnswersByIndex = [];
$hasSubmittedNativeResponse = false;

if ($alreadyCompleted) {
    $nativeResponseStmt = $pdo->prepare(
        'SELECT answers_json
         FROM survey_native_responses
         WHERE survey_id = :survey_id AND user_id = :user_id
         LIMIT 1'
    );
    $nativeResponseStmt->execute([
        'survey_id' => $surveyId,
        'user_id' => $user['id'],
    ]);
    $answersJson = $nativeResponseStmt->fetchColumn();

    if (is_string($answersJson) && $answersJson !== '') {
        $decodedAnswers = json_decode($answersJson, true);
        if (is_array($decodedAnswers)) {
            foreach ($decodedAnswers as $idx => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $questionIndex = isset($entry['question_index']) && is_numeric($entry['question_index'])
                    ? (int) $entry['question_index']
                    : (int) $idx;
                $submittedAnswersByIndex[$questionIndex] = trim((string) ($entry['answer'] ?? ''));
            }
            $hasSubmittedNativeResponse = $submittedAnswersByIndex !== [];
        }
    }
}

$canComplete = !$alreadyCompleted
    && $survey['status'] === SURVEY_STATUS_APPROVED
    && (!$isOwner || $canCompleteOwnSurvey);

$status = (string) $survey['status'];
$statusClass = match ($status) {
    SURVEY_STATUS_APPROVED => 'badge-approved',
    SURVEY_STATUS_REJECTED => 'badge-rejected',
    default => 'badge-pending',
};
$description = trim((string) ($survey['description'] ?? ''));
$estimatedTime = $survey['estimated_minutes'] !== null
    ? e((string) $survey['estimated_minutes']) . ' minutes'
    : 'Not specified';
$isNativeSurvey = is_native_survey($survey);
$nativeQuestions = decode_survey_schema_questions((string) ($survey['survey_schema_json'] ?? ''));

$pageTitle = 'Survey Details';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section card card-pad">
  <div class="card-head">
    <h1 class="page-title"><?= e($survey['title']) ?></h1>
    <span class="badge <?= e($statusClass) ?>"><?= e($status) ?></span>
  </div>

  <p class="muted"><?= $description !== '' ? nl2br(e($description)) : 'No description provided.' ?></p>

  <div class="meta-list">
    <p class="meta-item"><span class="meta-label">Category:</span> <?= e($survey['category']) ?></p>
    <p class="meta-item"><span class="meta-label">Mode:</span> <?= $isNativeSurvey ? 'Native (in-app)' : 'Legacy Google Form' ?></p>
    <p class="meta-item"><span class="meta-label">Estimated Time:</span> <?= $estimatedTime ?></p>
    <p class="meta-item"><span class="meta-label">Reward:</span> +<?= e((string) $survey['reward_points']) ?> point(s)</p>
    <p class="meta-item"><span class="meta-label">Posted By:</span> <?= e($survey['posted_by']) ?></p>
    <p class="meta-item"><span class="meta-label">Created:</span> <?= e(date('M j, Y', strtotime($survey['created_at']))) ?></p>
  </div>

  <?php if (!$canComplete && !$isOwner && !$alreadyCompleted): ?>
    <p class="notice">You can answer this survey once it is active and open for responses.</p>
  <?php endif; ?>

  <?php if ($isNativeSurvey): ?>
    <section class="section card card-pad">
      <div class="card-head">
        <h2 class="card-title">Answer Survey</h2>
        <span class="survey-question-count"><?= e((string) count($nativeQuestions)) ?> question(s)</span>
      </div>

      <?php if ($nativeQuestions === []): ?>
        <p class="muted">This survey has no configured questions.</p>
      <?php else: ?>
        <form action="<?= e(url('/actions/submit_native_response_action.php')) ?>" method="post" class="form-grid">
          <?= csrf_field() ?>
          <input type="hidden" name="survey_id" value="<?= e((string) $survey['id']) ?>">

          <?php foreach ($nativeQuestions as $index => $question): ?>
            <?php
            $questionType = (string) ($question['type'] ?? native_question_type_short_text());
            $questionOptions = $question['options'] ?? [];
            $submittedAnswer = (string) ($submittedAnswersByIndex[$index] ?? '');
            ?>
            <div class="field survey-question-card">
              <label class="field-label">
                Q<?= e((string) ($index + 1)) ?>. <?= e((string) ($question['title'] ?? ('Question ' . ($index + 1)))) ?>
              </label>

              <?php if ($questionType === native_question_type_multiple_choice() && is_array($questionOptions) && $questionOptions !== []): ?>
                <div class="form-grid survey-option-list">
                  <?php foreach ($questionOptions as $optionIndex => $optionText): ?>
                    <label for="answer_<?= e((string) $index) ?>_<?= e((string) $optionIndex) ?>" class="survey-option-row">
                      <input
                        id="answer_<?= e((string) $index) ?>_<?= e((string) $optionIndex) ?>"
                        name="answers[<?= e((string) $index) ?>]"
                        type="radio"
                        value="<?= e((string) $optionText) ?>"
                        <?= $submittedAnswer !== '' && $submittedAnswer === (string) $optionText ? 'checked' : '' ?>
                        <?= $canComplete ? 'required' : 'disabled' ?>
                      >
                      <?= e((string) $optionText) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <input
                  id="answer_<?= e((string) $index) ?>"
                  name="answers[<?= e((string) $index) ?>]"
                  type="text"
                  class="input"
                  maxlength="2000"
                  value="<?= e($submittedAnswer) ?>"
                  <?= $canComplete ? 'required' : 'disabled' ?>
                >
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="actions-row">
            <button type="submit" class="btn btn-success" <?= $canComplete ? '' : 'disabled' ?>>Submit Answers</button>
          </div>

          <?php if ($canComplete): ?>
            <p class="muted small">You can submit only once for this survey. Make sure your answers are final.</p>
          <?php elseif ($alreadyCompleted && $hasSubmittedNativeResponse): ?>
            <p class="muted small">Your submitted answers are shown above in read-only mode.</p>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </section>
  <?php else: ?>
    <div class="actions-row">
      <a href="<?= e((string) $survey['form_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Open Survey</a>

      <form action="<?= e(url('/actions/complete_survey_action.php')) ?>" method="post" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="survey_id" value="<?= e((string) $survey['id']) ?>">
        <button type="submit" class="btn btn-success" <?= $canComplete ? '' : 'disabled' ?>>Mark as Completed</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isOwner && !$isAdmin): ?>
    <p class="muted small">You cannot earn points from your own survey.</p>
  <?php elseif ($isOwner && $isAdmin): ?>
    <p class="muted small">Admin override enabled: you can complete your own survey once.</p>
  <?php elseif ($alreadyCompleted): ?>
    <p class="muted small">You already completed this survey.</p>
  <?php elseif ($survey['status'] !== SURVEY_STATUS_APPROVED): ?>
    <p class="muted small">Only published surveys can be completed.</p>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
