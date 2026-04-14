USE surveyswap;

START TRANSACTION;

-- 1) Users: starter balance default becomes 5.
ALTER TABLE users
  MODIFY points INT UNSIGNED NOT NULL DEFAULT 5;

-- 2) Surveys: add campaign budgeting fields.
ALTER TABLE surveys
  ADD COLUMN target_completions SMALLINT UNSIGNED NOT NULL DEFAULT 5 AFTER reward_points,
  ADD COLUMN current_completions SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER target_completions,
  ADD COLUMN listing_fee TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER current_completions,
  ADD COLUMN total_budget INT UNSIGNED NOT NULL DEFAULT 0 AFTER listing_fee,
  ADD COLUMN remaining_budget INT UNSIGNED NOT NULL DEFAULT 0 AFTER total_budget;

-- 3) Completion rows now store exact reward paid for that completion.
ALTER TABLE survey_completions
  ADD COLUMN reward_given TINYINT UNSIGNED NULL AFTER user_id;

UPDATE survey_completions sc
INNER JOIN surveys s ON s.id = sc.survey_id
SET sc.reward_given = s.reward_points
WHERE sc.reward_given IS NULL;

ALTER TABLE survey_completions
  MODIFY reward_given TINYINT UNSIGNED NOT NULL;

-- 4) Transactions: rename related_survey_id -> survey_id and add optional related_user_id.
ALTER TABLE point_transactions
  DROP FOREIGN KEY fk_points_related_survey;

ALTER TABLE point_transactions
  CHANGE COLUMN related_survey_id survey_id INT UNSIGNED NULL,
  ADD COLUMN related_user_id INT UNSIGNED NULL AFTER survey_id,
  MODIFY reason VARCHAR(120) NOT NULL;

ALTER TABLE point_transactions
  ADD CONSTRAINT fk_points_survey
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_points_related_user
    FOREIGN KEY (related_user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 5) Backfill budgets and completion counters for existing surveys.
UPDATE surveys s
LEFT JOIN (
  SELECT survey_id, COUNT(*) AS completion_count
  FROM survey_completions
  GROUP BY survey_id
) c ON c.survey_id = s.id
SET
  s.reward_points = CASE
    WHEN s.reward_points < 1 THEN 1
    WHEN s.reward_points > 3 THEN 3
    ELSE s.reward_points
  END,
  s.target_completions = 5,
  s.current_completions = LEAST(IFNULL(c.completion_count, 0), 5),
  s.listing_fee = 1,
  s.total_budget = 1 + (
    CASE
      WHEN s.reward_points < 1 THEN 1
      WHEN s.reward_points > 3 THEN 3
      ELSE s.reward_points
    END * 5
  ),
  s.remaining_budget = GREATEST(
    0,
    (1 + (
      CASE
        WHEN s.reward_points < 1 THEN 1
        WHEN s.reward_points > 3 THEN 3
        ELSE s.reward_points
      END * 5
    )) - (IFNULL(c.completion_count, 0) *
      CASE
        WHEN s.reward_points < 1 THEN 1
        WHEN s.reward_points > 3 THEN 3
        ELSE s.reward_points
      END
    )
  );

-- 6) Migrate status values: approved -> active, and auto-close full campaigns.
UPDATE surveys SET status = 'active' WHERE status = 'approved';

UPDATE surveys
SET status = 'completed'
WHERE status = 'active' AND current_completions >= target_completions;

ALTER TABLE surveys
  MODIFY status ENUM('pending', 'active', 'completed', 'rejected', 'paused') NOT NULL DEFAULT 'pending';

-- 7) Add helpful indexes for feed and reporting.
CREATE INDEX idx_surveys_feed_sort ON surveys (status, reward_points, created_at);
CREATE INDEX idx_points_survey ON point_transactions (survey_id);

COMMIT;
