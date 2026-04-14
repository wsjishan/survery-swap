<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

load_env_file(dirname(__DIR__) . '/.env');

const APP_NAME = 'SurveySwap';

const STARTER_POINTS = 5;

const SURVEY_REWARD_TIERS = [1, 2, 3];
const SURVEY_DEFAULT_REWARD_POINTS = 1;
const SURVEY_MIN_TARGET_COMPLETIONS = 5;
const SURVEY_MAX_TARGET_COMPLETIONS = 20;

const SURVEY_LISTING_FEE = 1;
const SURVEY_SUBMIT_COST = SURVEY_LISTING_FEE;
const SURVEY_REQUIRES_MODERATION = true;

const SURVEY_STATUS_PENDING = 'pending';
const SURVEY_STATUS_ACTIVE = 'active';
const SURVEY_STATUS_COMPLETED = 'completed';
const SURVEY_STATUS_REJECTED = 'rejected';
const SURVEY_STATUS_PAUSED = 'paused';

// Backward-compatible alias used by existing pages.
const SURVEY_STATUS_APPROVED = SURVEY_STATUS_ACTIVE;

const TX_TYPE_CREDIT = 'credit';
const TX_TYPE_DEBIT = 'debit';

const TX_REASON_STARTER = 'starter_bonus';
const TX_REASON_COMPLETION = 'survey_completion_reward';
const TX_REASON_SURVEY_PUBLISH = 'survey_publish_budget';
const TX_REASON_SUBMISSION = TX_REASON_SURVEY_PUBLISH;
const TX_REASON_ADMIN_ADJUSTMENT = 'admin_adjustment';

define('APP_ENV', env_value('APP_ENV', 'local') ?? 'local');
define('BASE_URL_OVERRIDE', env_value('BASE_URL_OVERRIDE', '') ?? '');
define('DB_HOST', env_value('DB_HOST', '') ?? '');
define('DB_PORT', env_value('DB_PORT', '3306') ?? '3306');
define('DB_NAME', env_value('DB_NAME', '') ?? '');
define('DB_USER', env_value('DB_USER', '') ?? '');
define('DB_PASS', env_value('DB_PASS', '') ?? '');

if (date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('Asia/Dhaka');
}
