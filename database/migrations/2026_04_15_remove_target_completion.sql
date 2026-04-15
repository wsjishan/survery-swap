USE surveyswap;

START TRANSACTION;

-- Remove target-based completion caps.
ALTER TABLE surveys
  DROP CHECK chk_surveys_target_range,
  DROP CHECK chk_surveys_completion_cap;

ALTER TABLE surveys
  DROP COLUMN current_completions,
  DROP COLUMN target_completions;

-- Expand reward tiers to 1..5 points.
ALTER TABLE surveys
  DROP CHECK chk_surveys_reward_tier,
  ADD CONSTRAINT chk_surveys_reward_tier CHECK (reward_points IN (1, 2, 3, 4, 5));

-- Simplified publish pricing: cost = reward x 2.
UPDATE surveys
SET listing_fee = 0,
    total_budget = reward_points * 2,
    remaining_budget = reward_points * 2;

-- Re-open legacy auto-completed campaigns now that there is no target cap.
UPDATE surveys
SET status = 'active'
WHERE status = 'completed';

COMMIT;
