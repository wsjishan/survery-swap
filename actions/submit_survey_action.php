<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if (!is_post()) {
    redirect('/submit-survey.php');
}

require_valid_csrf('/submit-survey.php');

$user = current_user();
$userId = (int) $user['id'];

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$rawQuestions = $_POST['questions'] ?? [];
$rawQuestionTypes = $_POST['question_types'] ?? [];
$rawQuestionOptions = $_POST['question_options'] ?? [];
$nativeQuestions = normalize_native_survey_questions(
    is_array($rawQuestions) ? $rawQuestions : [],
    is_array($rawQuestionTypes) ? $rawQuestionTypes : [],
    is_array($rawQuestionOptions) ? $rawQuestionOptions : []
);
$category = trim((string) ($_POST['category'] ?? ''));
$targetAudience = trim((string) ($_POST['target_audience'] ?? 'General'));
$estimatedMinutesRaw = trim((string) ($_POST['estimated_minutes'] ?? ''));
$rewardPointsRaw = trim((string) ($_POST['reward_points'] ?? ''));
$estimatedMinutes = null;
$rewardPoints = 0;
$allowedCategories = survey_categories();

$errors = [];

if ($message = validate_required($title, 'Title')) {
    $errors['title'] = $message;
}
if ($message = validate_required($category, 'Category')) {
    $errors['category'] = $message;
}
if ($category !== '' && !in_array($category, $allowedCategories, true)) {
    $errors['category'] = 'Please select a valid category.';
}
if ($message = validate_required($targetAudience, 'Target audience')) {
    $errors['target_audience'] = $message;
}
if ($estimatedMinutesRaw !== '') {
    if ($message = validate_positive_int($estimatedMinutesRaw, 'Estimated completion time')) {
        $errors['estimated_minutes'] = $message;
    } else {
        $estimatedMinutes = (int) $estimatedMinutesRaw;
        if ($estimatedMinutes > 120) {
            $errors['estimated_minutes'] = 'Estimated completion time must be 120 minutes or less.';
        }
    }
}

$questionErrors = validate_native_survey_questions($nativeQuestions);
if ($questionErrors !== []) {
    $errors['questions'] = $questionErrors[0];
}

if ($message = validate_positive_int($rewardPointsRaw, 'Reward per completion')) {
    $errors['reward_points'] = $message;
} else {
    $rewardPoints = (int) $rewardPointsRaw;
    if (!is_valid_reward_tier($rewardPoints)) {
        $errors['reward_points'] = 'Reward tier must be between 1 and 5 points.';
    }
}

$totalCost = 0;
$rewardPool = 0;
if (!$errors) {
    $rewardPool = calculate_survey_reward_pool_points($rewardPoints);
    $totalCost = calculate_survey_total_cost($rewardPoints);
}

if (!$errors && (int) $user['points'] < $totalCost) {
    $errors['points'] = sprintf(
        'You need %d points to publish this survey campaign.',
        $totalCost
    );
}

if ($errors) {
    set_validation_errors($errors);
    set_old_input([
        'title' => $title,
        'description' => $description,
        'questions' => is_array($rawQuestions) ? $rawQuestions : [],
        'question_types' => is_array($rawQuestionTypes) ? $rawQuestionTypes : [],
        'question_options' => is_array($rawQuestionOptions) ? $rawQuestionOptions : [],
        'category' => $category,
        'target_audience' => $targetAudience,
        'estimated_minutes' => $estimatedMinutesRaw,
        'reward_points' => $rewardPointsRaw,
    ]);
    if (isset($errors['points'])) {
        set_flash('warning', $errors['points']);
    }
    redirect('/submit-survey.php');
}

try {
    $pdo = db();

    $pdo->beginTransaction();

    $lockStmt = $pdo->prepare('SELECT points FROM users WHERE id = :id FOR UPDATE');
    $lockStmt->execute(['id' => $userId]);
    $currentPoints = (int) $lockStmt->fetchColumn();

    if ($currentPoints < $totalCost) {
        $pdo->rollBack();
        set_flash('warning', sprintf('You need %d points to publish this survey campaign.', $totalCost));
        redirect('/submit-survey.php');
    }

    $surveyStatus = SURVEY_REQUIRES_MODERATION ? SURVEY_STATUS_PENDING : SURVEY_STATUS_ACTIVE;
    $listingFee = survey_listing_fee();

    $insertSurvey = $pdo->prepare(
        'INSERT INTO surveys (
            user_id, source_type, title, description, form_url, survey_schema_json, category, target_audience,
            estimated_minutes, reward_points, listing_fee, total_budget, remaining_budget, status
         ) VALUES (
            :user_id, :source_type, :title, :description, :form_url, :survey_schema_json, :category, :target_audience,
            :estimated_minutes, :reward_points, :listing_fee, :total_budget, :remaining_budget, :status
         )'
    );

    $surveySchemaJson = json_encode(
        ['questions' => $nativeQuestions],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($surveySchemaJson === false) {
        throw new RuntimeException('Failed to serialize survey questions.');
    }

    $insertSurvey->execute([
        'user_id' => $userId,
        'source_type' => survey_source_type_native(),
        'title' => $title,
        'description' => $description,
        'form_url' => null,
        'survey_schema_json' => $surveySchemaJson,
        'category' => $category,
        'target_audience' => $targetAudience,
        'estimated_minutes' => $estimatedMinutes,
        'reward_points' => $rewardPoints,
        'listing_fee' => $listingFee,
        'total_budget' => $totalCost,
        'remaining_budget' => $rewardPool,
        'status' => $surveyStatus,
    ]);

    $surveyId = (int) $pdo->lastInsertId();

    $deducted = debit_points(
        $pdo,
        $userId,
        $totalCost,
        TX_REASON_SURVEY_PUBLISH,
        $surveyId
    );

    if (!$deducted) {
        throw new RuntimeException('Failed to deduct points.');
    }

    $pdo->commit();

    if ($surveyStatus === SURVEY_STATUS_PENDING) {
        set_flash('success', 'Survey campaign submitted and awaiting admin approval.');
    } else {
        set_flash('success', 'Survey campaign published successfully.');
    }
    redirect('/my-surveys.php');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_validation_errors(['submit' => 'Submission failed. Please try again.']);
    set_old_input([
        'title' => $title,
        'description' => $description,
        'questions' => is_array($rawQuestions) ? $rawQuestions : [],
        'question_types' => is_array($rawQuestionTypes) ? $rawQuestionTypes : [],
        'question_options' => is_array($rawQuestionOptions) ? $rawQuestionOptions : [],
        'category' => $category,
        'target_audience' => $targetAudience,
        'estimated_minutes' => $estimatedMinutesRaw,
        'reward_points' => $rewardPointsRaw,
    ]);
    set_flash('danger', db_user_error_message($e));
    redirect('/submit-survey.php');
}
