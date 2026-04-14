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
    $stmt = db()->prepare(
        'UPDATE surveys
         SET status = :approved
         WHERE id = :id AND status = :pending'
    );
    $stmt->execute([
        'approved' => SURVEY_STATUS_APPROVED,
        'pending' => SURVEY_STATUS_PENDING,
        'id' => $surveyId,
    ]);

    if ($stmt->rowCount() === 1) {
        set_flash('success', 'Survey approved successfully.');
    } else {
        set_flash('warning', 'Survey could not be approved (already moderated or missing).');
    }
} catch (Throwable $e) {
    set_flash('danger', db_user_error_message($e));
}

redirect('/admin/moderation.php');
