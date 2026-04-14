<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if (!is_post()) {
    redirect('/surveys.php');
}

require_valid_csrf('/surveys.php');

$surveyId = isset($_POST['survey_id']) ? (int) $_POST['survey_id'] : 0;
$userId = (int) current_user_id();

if ($surveyId < 1) {
    set_flash('danger', 'Invalid survey selected.');
    redirect('/surveys.php');
}

try {
    $pdo = db();

    $pdo->beginTransaction();

    $surveyStmt = $pdo->prepare(
        'SELECT id, user_id, source_type, status, reward_points, target_completions,
            current_completions, remaining_budget
         FROM surveys
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $surveyStmt->execute(['id' => $surveyId]);
    $survey = $surveyStmt->fetch();

    if (!$survey) {
        $pdo->rollBack();
        set_flash('danger', 'Survey not found.');
        redirect('/surveys.php');
    }

    if ((int) $survey['user_id'] === $userId) {
        $pdo->rollBack();
        set_flash('warning', 'You cannot complete your own survey.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    if ((string) ($survey['source_type'] ?? '') === survey_source_type_native()) {
        $pdo->rollBack();
        set_flash('warning', 'Submit answers in-app to complete this survey.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    if ($survey['status'] !== SURVEY_STATUS_ACTIVE) {
        $pdo->rollBack();
        set_flash('warning', 'Only active surveys can be completed.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    $existsStmt = $pdo->prepare(
        'SELECT id
         FROM survey_completions
         WHERE survey_id = :survey_id AND user_id = :user_id
         LIMIT 1'
    );
    $existsStmt->execute([
        'survey_id' => $surveyId,
        'user_id' => $userId,
    ]);

    if ($existsStmt->fetch()) {
        $pdo->rollBack();
        set_flash('info', 'You have already completed this survey.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    $rewardPoints = max(1, (int) $survey['reward_points']);
    $targetCompletions = (int) $survey['target_completions'];
    $currentCompletions = (int) $survey['current_completions'];
    $remainingBudget = (int) $survey['remaining_budget'];

    if ($currentCompletions >= $targetCompletions) {
        $pdo->rollBack();
        set_flash('info', 'This survey campaign already reached its target completions.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    if ($remainingBudget < $rewardPoints) {
        $pdo->rollBack();
        set_flash('warning', 'This survey campaign has insufficient remaining budget.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    $insertCompletion = $pdo->prepare(
        'INSERT INTO survey_completions (survey_id, user_id, reward_given)
         VALUES (:survey_id, :user_id, :reward_given)'
    );
    $insertCompletion->execute([
        'survey_id' => $surveyId,
        'user_id' => $userId,
        'reward_given' => $rewardPoints,
    ]);

    $credited = credit_points(
        $pdo,
        $userId,
        $rewardPoints,
        TX_REASON_COMPLETION,
        $surveyId,
        (int) $survey['user_id']
    );

    if (!$credited) {
        throw new RuntimeException('Could not award completion points.');
    }

    $newCurrentCompletions = $currentCompletions + 1;
    $newRemainingBudget = max(0, $remainingBudget - $rewardPoints);
    $newStatus = $newCurrentCompletions >= $targetCompletions
        ? SURVEY_STATUS_COMPLETED
        : SURVEY_STATUS_ACTIVE;

    $updateSurvey = $pdo->prepare(
        'UPDATE surveys
         SET current_completions = :current_completions,
             remaining_budget = :remaining_budget,
             status = :status
         WHERE id = :id'
    );
    $updateSurvey->execute([
        'current_completions' => $newCurrentCompletions,
        'remaining_budget' => $newRemainingBudget,
        'status' => $newStatus,
        'id' => $surveyId,
    ]);

    $pdo->commit();

    if ($newStatus === SURVEY_STATUS_COMPLETED) {
        set_flash('success', sprintf('Completion recorded. You earned +%d points and the campaign is now completed.', $rewardPoints));
    } else {
        set_flash('success', sprintf('Completion recorded. You earned +%d points.', $rewardPoints));
    }
    redirect('/survey-details.php?id=' . $surveyId);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('danger', db_user_error_message($e));
    redirect('/survey-details.php?id=' . $surveyId);
}
