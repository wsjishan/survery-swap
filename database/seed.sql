USE surveyswap;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE point_transactions;
TRUNCATE TABLE survey_native_responses;
TRUNCATE TABLE survey_completions;
TRUNCATE TABLE surveys;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Demo credentials:
-- Admin: admin@surveyswap.test / Admin@123
-- User password for demo users: User@1234

INSERT INTO users (id, name, email, password, role, points) VALUES
  (1, 'Mahmudul Hasan', 'admin@surveyswap.test', '$2y$12$MjmaAyIOPoX2bGukTTglOuFrupnIcxmIJ97F8k2.N2oL5xkVkT0Ia', 'admin', 120),
  (2, 'Nusrat Jahan', 'alice@surveyswap.test', '$2y$12$U5CTI5WA2W74ETuR2ksT7uUcptWk9rX6xCnGodSoomRD0xQuWJ/3a', 'user', 23),
  (3, 'Sabbir Ahmed', 'bob@surveyswap.test', '$2y$12$zn9UI0UhHGb/Zq5WKqWPKO0hL8ajmYsIPZ/ijXy//tvsYb8MrVqEi', 'user', 19);

INSERT INTO surveys (
  id, user_id, title, description, form_url, category, target_audience,
  estimated_minutes, reward_points,
  listing_fee, total_budget, remaining_budget, status, created_at
) VALUES
  (1, 2, 'Social Media Usage and GPA',
   'Short student survey on social media usage and academic performance.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-social-media/viewform',
   'Education', 'Undergraduate Students', 5, 3,
   0, 6, 3, 'active', NOW() - INTERVAL 5 DAY),
  (2, 3, 'Remote Learning Satisfaction Study',
   'Quick research survey on online class experience and satisfaction.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-remote-learning/viewform',
   'Education', 'College Students', 6, 2,
   0, 4, 4, 'pending', NOW() - INTERVAL 2 DAY);

INSERT INTO survey_completions (id, survey_id, user_id, reward_given, completed_at) VALUES
  (1, 1, 3, 3, NOW() - INTERVAL 1 DAY);

INSERT INTO point_transactions (user_id, type, points, reason, survey_id, related_user_id, created_at) VALUES
  (1, 'credit', 50, 'admin_adjustment', NULL, NULL, NOW() - INTERVAL 7 DAY),
  (2, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 6 DAY),
  (2, 'debit', 6, 'survey_publish_budget', 1, NULL, NOW() - INTERVAL 5 DAY),
  (3, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 6 DAY),
  (3, 'debit', 4, 'survey_publish_budget', 2, NULL, NOW() - INTERVAL 2 DAY),
  (3, 'credit', 3, 'survey_completion_reward', 1, 2, NOW() - INTERVAL 1 DAY);

ALTER TABLE users AUTO_INCREMENT = 4;
ALTER TABLE surveys AUTO_INCREMENT = 3;
ALTER TABLE survey_completions AUTO_INCREMENT = 2;
