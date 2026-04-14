USE surveyswap;

START TRANSACTION;

ALTER TABLE surveys
  ADD COLUMN source_type ENUM('legacy_google', 'native') NOT NULL DEFAULT 'legacy_google' AFTER user_id,
  MODIFY form_url VARCHAR(500) NULL,
  ADD COLUMN survey_schema_json JSON NULL AFTER form_url;

UPDATE surveys
SET source_type = 'legacy_google'
WHERE source_type IS NULL;

CREATE TABLE IF NOT EXISTS survey_native_responses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  survey_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  answers_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_native_responses_survey
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_native_responses_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_native_survey_user_once (survey_id, user_id),
  INDEX idx_native_responses_user_date (user_id, created_at)
) ENGINE=InnoDB;

CREATE INDEX idx_surveys_source_status ON surveys (source_type, status, created_at);

COMMIT;
