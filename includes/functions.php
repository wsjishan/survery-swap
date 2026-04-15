<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_base_path(string $value): string
{
    $value = trim($value);

    if ($value === '' || $value === '/') {
        return '';
    }

    if ($value[0] !== '/') {
        $value = '/' . $value;
    }

    return rtrim($value, '/');
}

function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $override = defined('BASE_URL_OVERRIDE') ? trim((string) BASE_URL_OVERRIDE) : '';
    if ($override !== '') {
        $basePath = normalize_base_path($override);
        return $basePath;
    }

    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    if ($scriptDir === '.' || $scriptDir === '\\') {
        $scriptDir = '/';
    }

    $scriptDir = rtrim($scriptDir, '/');
    if ($scriptDir === '') {
        $scriptDir = '/';
    }

    $projectRoot = realpath(__DIR__ . '/..') ?: '';
    $scriptFile = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: '';

    if ($projectRoot !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $projectRoot)) {
        $relativePath = ltrim(substr($scriptFile, strlen($projectRoot)), '/');
        $relativeDir = trim(str_replace('\\', '/', dirname($relativePath)), '/');

        if ($relativeDir === '.') {
            $relativeDir = '';
        }

        if ($relativeDir !== '') {
            $suffix = '/' . $relativeDir;
            if (str_ends_with($scriptDir, $suffix)) {
                $scriptDir = substr($scriptDir, 0, -strlen($suffix));
                if ($scriptDir === '') {
                    $scriptDir = '/';
                }
            }
        }
    }

    $basePath = normalize_base_path($scriptDir);
    return $basePath;
}

function url(string $path = '/'): string
{
    $base = app_base_path();
    $path = '/' . ltrim($path, '/');

    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function redirect_back(string $fallback = '/'): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? null;

    if ($referer) {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? null;

        if (!$refererHost || !$currentHost || strcasecmp($refererHost, $currentHost) === 0) {
            header('Location: ' . $referer);
            exit;
        }
    }

    redirect($fallback);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_get(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
}

function set_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    if (!$token || empty($_SESSION['_csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $token);
}

function require_valid_csrf(string $fallback = '/'): void
{
    if (!is_post() || !verify_csrf_token($_POST['_token'] ?? null)) {
        set_flash('danger', 'Invalid request token. Please try again.');
        redirect($fallback);
    }
}

function is_valid_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function is_valid_google_form_url(string $url): bool
{
    if (!is_valid_url($url)) {
        return false;
    }

    $host = (string) parse_url($url, PHP_URL_HOST);
    return str_contains($host, 'docs.google.com') || str_contains($host, 'forms.gle');
}

function survey_categories(): array
{
    return [
        'Education',
        'Research',
        'Health',
        'Psychology',
        'Technology',
        'Business',
        'Marketing',
        'Social Science',
        'Environment',
        'Public Policy',
        'Other',
    ];
}

function survey_reward_tiers(): array
{
    return SURVEY_REWARD_TIERS;
}

function is_valid_reward_tier(int $rewardPoints): bool
{
    return in_array($rewardPoints, survey_reward_tiers(), true);
}

function survey_listing_fee(): int
{
    return SURVEY_LISTING_FEE;
}

function calculate_survey_reward_pool_points(int $rewardPoints): int
{
    return $rewardPoints * SURVEY_REWARD_COST_MULTIPLIER;
}

function calculate_survey_total_cost(int $rewardPoints): int
{
    return calculate_survey_reward_pool_points($rewardPoints);
}

function survey_source_type_legacy(): string
{
    return 'legacy_google';
}

function survey_source_type_native(): string
{
    return 'native';
}

function is_native_survey(array $survey): bool
{
    return (string) ($survey['source_type'] ?? survey_source_type_legacy()) === survey_source_type_native();
}

function native_survey_question_min(): int
{
    return 1;
}

function native_survey_question_max(): int
{
    return 15;
}

function native_question_type_short_text(): string
{
    return 'short_text';
}

function native_question_type_multiple_choice(): string
{
    return 'multiple_choice';
}

function native_question_type_options(): array
{
    return [
        native_question_type_short_text(),
        native_question_type_multiple_choice(),
    ];
}

function normalize_multiple_choice_options(string $rawOptions): array
{
    $lines = preg_split('/\r\n|\r|\n/', $rawOptions) ?: [];
    $options = [];

    foreach ($lines as $line) {
        $option = trim((string) $line);
        if ($option === '') {
            continue;
        }

        $options[] = substr($option, 0, 120);
    }

    $options = array_values(array_unique($options));

    return array_slice($options, 0, 10);
}

function normalize_native_survey_questions(array $rawQuestions, array $rawQuestionTypes = [], array $rawQuestionOptions = []): array
{
    $questions = [];

    foreach ($rawQuestions as $index => $rawQuestion) {
        $text = trim((string) $rawQuestion);
        if ($text === '') {
            continue;
        }

        $type = trim((string) ($rawQuestionTypes[$index] ?? native_question_type_short_text()));
        if (!in_array($type, native_question_type_options(), true)) {
            $type = native_question_type_short_text();
        }

        $question = [
            'type' => $type,
            'title' => substr($text, 0, 240),
            'required' => true,
        ];

        if ($type === native_question_type_multiple_choice()) {
            $options = normalize_multiple_choice_options((string) ($rawQuestionOptions[$index] ?? ''));
            $question['options'] = $options;
        }

        $questions[] = $question;
    }

    return $questions;
}

function validate_native_survey_questions(array $questions): array
{
    $errors = [];
    $count = count($questions);

    if ($count < native_survey_question_min()) {
        $errors[] = 'Add at least one survey question.';
    }

    if ($count > native_survey_question_max()) {
        $errors[] = sprintf('You can add up to %d questions in V1.', native_survey_question_max());
    }

    foreach ($questions as $index => $question) {
        $title = trim((string) ($question['title'] ?? ''));
        $type = trim((string) ($question['type'] ?? native_question_type_short_text()));
        if ($title === '') {
            $errors[] = sprintf('Question %d is required.', $index + 1);
            continue;
        }

        if (strlen($title) > 240) {
            $errors[] = sprintf('Question %d must be 240 characters or less.', $index + 1);
        }

        if (!in_array($type, native_question_type_options(), true)) {
            $errors[] = sprintf('Question %d has an invalid type.', $index + 1);
            continue;
        }

        if ($type === native_question_type_multiple_choice()) {
            $options = $question['options'] ?? [];
            if (!is_array($options)) {
                $errors[] = sprintf('Question %d options are invalid.', $index + 1);
                continue;
            }

            $normalizedOptions = [];
            foreach ($options as $option) {
                $optionText = trim((string) $option);
                if ($optionText === '') {
                    continue;
                }
                $normalizedOptions[] = substr($optionText, 0, 120);
            }

            $normalizedOptions = array_values(array_unique($normalizedOptions));

            if (count($normalizedOptions) < 2) {
                $errors[] = sprintf('Question %d needs at least 2 options.', $index + 1);
                continue;
            }

            if (count($normalizedOptions) > 10) {
                $errors[] = sprintf('Question %d can have up to 10 options.', $index + 1);
                continue;
            }
        }
    }

    return $errors;
}

function decode_survey_schema_questions(?string $surveySchemaJson): array
{
    if (!$surveySchemaJson) {
        return [];
    }

    $decoded = json_decode($surveySchemaJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $questions = $decoded['questions'] ?? [];
    if (!is_array($questions)) {
        return [];
    }

    return $questions;
}
