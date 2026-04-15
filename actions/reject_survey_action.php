<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (!is_post()) {
    redirect('/admin/moderation.php');
}

require_valid_csrf('/admin/moderation.php');

$surveyId = isset($_POST['survey_id']) ? (int) $_POST['survey_id'] : 0;
if ($surveyId < 1) {
    set_flash('danger', 'Invalid survey selected.');
    redirect('/admin/moderation.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $surveyStmt = $pdo->prepare(
        'SELECT id, user_id, status, total_budget, remaining_budget
         FROM surveys
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $surveyStmt->execute([
        'id' => $surveyId,
    ]);
    $survey = $surveyStmt->fetch();

    if (!$survey) {
        $pdo->rollBack();
        set_flash('warning', 'Survey could not be rejected (already moderated or missing).');
        redirect('/admin/moderation.php');
    }

    if ((string) ($survey['status'] ?? '') !== SURVEY_STATUS_PENDING) {
        $pdo->rollBack();
        set_flash('warning', 'Survey could not be rejected (already moderated or missing).');
        redirect('/admin/moderation.php');
    }

    $updateStmt = $pdo->prepare(
        'UPDATE surveys
         SET status = :rejected,
             remaining_budget = 0
         WHERE id = :id AND status = :pending'
    );
    $updateStmt->execute([
        'rejected' => SURVEY_STATUS_REJECTED,
        'pending' => SURVEY_STATUS_PENDING,
        'id' => $surveyId,
    ]);

    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException('Survey moderation state changed during rejection.');
    }

    $refundPoints = max(0, (int) ($survey['total_budget'] ?? 0));
    if ($refundPoints > 0) {
        $refunded = credit_points(
            $pdo,
            (int) $survey['user_id'],
            $refundPoints,
            TX_REASON_SURVEY_REJECT_REFUND,
            $surveyId
        );

        if (!$refunded) {
            throw new RuntimeException('Could not refund reserved survey budget.');
        }
    }

    $pdo->commit();

    if ($refundPoints > 0) {
        set_flash('success', sprintf('Survey rejected and %d point(s) refunded to the survey owner.', $refundPoints));
    } else {
        set_flash('success', 'Survey rejected successfully.');
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('danger', db_user_error_message($e));
}

redirect('/admin/moderation.php');
