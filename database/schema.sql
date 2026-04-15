CREATE DATABASE IF NOT EXISTS surveyswap
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE surveyswap;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  points INT UNSIGNED NOT NULL DEFAULT 20,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE surveys (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  source_type ENUM('legacy_google', 'native') NOT NULL DEFAULT 'legacy_google',
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  form_url VARCHAR(500) NULL,
  survey_schema_json JSON NULL,
  category VARCHAR(100) NOT NULL,
  target_audience VARCHAR(150) NOT NULL DEFAULT 'General',
  estimated_minutes TINYINT UNSIGNED NULL,
  reward_points TINYINT UNSIGNED NOT NULL DEFAULT 1,
  listing_fee TINYINT UNSIGNED NOT NULL DEFAULT 0,
  total_budget INT UNSIGNED NOT NULL,
  remaining_budget INT UNSIGNED NOT NULL,
  status ENUM('pending', 'active', 'completed', 'rejected', 'paused') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_surveys_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_surveys_reward_tier CHECK (reward_points IN (1, 2, 3, 4, 5)),
  CONSTRAINT chk_surveys_budget_non_negative CHECK (remaining_budget <= total_budget),
  INDEX idx_surveys_feed_sort (status, reward_points, created_at),
  INDEX idx_surveys_status_created (status, created_at),
  INDEX idx_surveys_user (user_id),
  INDEX idx_surveys_source_status (source_type, status, created_at)
) ENGINE=InnoDB;

CREATE TABLE survey_completions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  survey_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reward_given TINYINT UNSIGNED NOT NULL,
  completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_completions_survey
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_completions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_survey_user_once (survey_id, user_id),
  INDEX idx_completions_user_date (user_id, completed_at)
) ENGINE=InnoDB;

CREATE TABLE survey_native_responses (
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

CREATE TABLE point_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('credit', 'debit') NOT NULL,
  points INT UNSIGNED NOT NULL,
  reason VARCHAR(120) NOT NULL,
  survey_id INT UNSIGNED NULL,
  related_user_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_points_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_points_survey
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_points_related_user
    FOREIGN KEY (related_user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_points_user_date (user_id, created_at),
  INDEX idx_points_reason (reason),
  INDEX idx_points_survey (survey_id)
) ENGINE=InnoDB;
