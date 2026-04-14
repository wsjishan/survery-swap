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
$answersRaw = $_POST['answers'] ?? [];

if ($surveyId < 1) {
    set_flash('danger', 'Invalid survey selected.');
    redirect('/surveys.php');
}

if (!is_array($answersRaw)) {
    $answersRaw = [];
}

try {
    $pdo = db();

    $pdo->beginTransaction();

    $surveyStmt = $pdo->prepare(
        'SELECT id, user_id, source_type, survey_schema_json, status, reward_points,
            target_completions, current_completions, remaining_budget
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

    if ((string) ($survey['source_type'] ?? '') !== survey_source_type_native()) {
        $pdo->rollBack();
        set_flash('warning', 'This survey uses legacy completion mode.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    if ((int) $survey['user_id'] === $userId) {
        $pdo->rollBack();
        set_flash('warning', 'You cannot complete your own survey.');
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

    $questions = decode_survey_schema_questions((string) ($survey['survey_schema_json'] ?? ''));
    if ($questions === []) {
        $pdo->rollBack();
        set_flash('danger', 'This survey is missing question data.');
        redirect('/survey-details.php?id=' . $surveyId);
    }

    $normalizedAnswers = [];

    foreach ($questions as $index => $question) {
        $prompt = trim((string) ($question['title'] ?? ''));
        $questionType = trim((string) ($question['type'] ?? native_question_type_short_text()));
        $answer = trim((string) ($answersRaw[$index] ?? ''));

        if ($answer === '') {
            $pdo->rollBack();
            set_flash('warning', sprintf('Answer for question %d is required.', $index + 1));
            redirect('/survey-details.php?id=' . $surveyId);
        }

        if ($questionType === native_question_type_multiple_choice()) {
            $options = $question['options'] ?? [];
            if (!is_array($options) || $options === []) {
                $pdo->rollBack();
                set_flash('danger', sprintf('Question %d has invalid options.', $index + 1));
                redirect('/survey-details.php?id=' . $surveyId);
            }

            $normalizedOptions = [];
            foreach ($options as $option) {
                $optionText = trim((string) $option);
                if ($optionText !== '') {
                    $normalizedOptions[] = $optionText;
                }
            }

            if (!in_array($answer, $normalizedOptions, true)) {
                $pdo->rollBack();
                set_flash('warning', sprintf('Select a valid option for question %d.', $index + 1));
                redirect('/survey-details.php?id=' . $surveyId);
            }
        }

        $normalizedAnswers[] = [
            'question_index' => $index,
            'type' => $questionType,
            'question' => substr($prompt, 0, 240),
            'answer' => substr($answer, 0, 2000),
        ];
    }

    $answersJson = json_encode($normalizedAnswers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($answersJson === false) {
        throw new RuntimeException('Failed to serialize survey responses.');
    }

    $insertNativeResponse = $pdo->prepare(
        'INSERT INTO survey_native_responses (survey_id, user_id, answers_json)
         VALUES (:survey_id, :user_id, :answers_json)'
    );
    $insertNativeResponse->execute([
        'survey_id' => $surveyId,
        'user_id' => $userId,
        'answers_json' => $answersJson,
    ]);

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
        set_flash('success', sprintf('Response submitted. You earned +%d points and this campaign is now completed.', $rewardPoints));
    } else {
        set_flash('success', sprintf('Response submitted. You earned +%d points.', $rewardPoints));
    }

    redirect('/survey-details.php?id=' . $surveyId);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('danger', db_user_error_message($e));
    redirect('/survey-details.php?id=' . $surveyId);
}
