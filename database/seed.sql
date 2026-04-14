USE surveyswap;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE point_transactions;
TRUNCATE TABLE survey_completions;
TRUNCATE TABLE surveys;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Demo credentials:
-- Admin: admin@surveyswap.test / Admin@123
-- User password for all demo users: User@1234

INSERT INTO users (id, name, email, password, role, points) VALUES
  (1, 'Admin User', 'admin@surveyswap.test', '$2y$12$MjmaAyIOPoX2bGukTTglOuFrupnIcxmIJ97F8k2.N2oL5xkVkT0Ia', 'admin', 120),
  (2, 'Alice Rahman', 'alice@surveyswap.test', '$2y$12$U5CTI5WA2W74ETuR2ksT7uUcptWk9rX6xCnGodSoomRD0xQuWJ/3a', 'user', 26),
  (3, 'Bob Karim', 'bob@surveyswap.test', '$2y$12$zn9UI0UhHGb/Zq5WKqWPKO0hL8ajmYsIPZ/ijXy//tvsYb8MrVqEi', 'user', 18),
  (4, 'Carol Sultana', 'carol@surveyswap.test', '$2y$12$cji1kfR7fLgf2KfXqmqJkeMY4BVj1ZWr9RlhlY2xIpc/ZMhQHuQda', 'user', 11),
  (5, 'David Hasan', 'david@surveyswap.test', '$2y$12$ihp2zPw8d6GFNueqLnoTRuhUGdV21.3js0pFFH43EAyzN2KcGSpsm', 'user', 16);

INSERT INTO surveys (
  id, user_id, title, description, form_url, category, target_audience,
  estimated_minutes, reward_points, target_completions, current_completions,
  listing_fee, total_budget, remaining_budget, status, created_at
) VALUES
  (1, 2, 'Social Media Usage and GPA',
   'Quick academic survey about social media usage patterns and GPA among university students.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-social-media/viewform',
   'Education', 'Undergraduate Students', 5, 3, 8, 3,
   1, 25, 16, 'active', NOW() - INTERVAL 8 DAY),
  (2, 3, 'Remote Learning Satisfaction Study',
   'Research survey focused on online learning quality, motivation, and satisfaction.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-remote-learning/viewform',
   'Education', 'College Students', 6, 2, 7, 2,
   1, 15, 11, 'active', NOW() - INTERVAL 6 DAY),
  (3, 4, 'Campus Mental Health Awareness',
   'Survey exploring awareness and usage of mental health support services on campus.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-mental-health/viewform',
   'Psychology', 'University Students', 7, 1, 5, 0,
   1, 6, 6, 'pending', NOW() - INTERVAL 2 DAY),
  (4, 5, 'Food Habit and Sleep Pattern Analysis',
   'Academic questionnaire on food habits and their relationship with sleep quality.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-food-sleep/viewform',
   'Health', 'Young Adults', 8, 2, 6, 0,
   1, 13, 13, 'active', NOW() - INTERVAL 1 DAY),
  (5, 1, 'Research Methods Confidence Poll',
   'Short poll for students to rate confidence in selecting and applying research methods.',
   'https://docs.google.com/forms/d/e/1FAIpQLSdA1-demo-research-methods/viewform',
   'Research', 'Students and Researchers', 4, 1, 5, 1,
   1, 6, 5, 'active', NOW() - INTERVAL 4 DAY);

INSERT INTO survey_completions (id, survey_id, user_id, reward_given, completed_at) VALUES
  (1, 1, 3, 3, NOW() - INTERVAL 5 DAY),
  (2, 1, 4, 3, NOW() - INTERVAL 4 DAY),
  (3, 2, 2, 2, NOW() - INTERVAL 3 DAY),
  (4, 1, 5, 3, NOW() - INTERVAL 2 DAY),
  (5, 2, 5, 2, NOW() - INTERVAL 1 DAY),
  (6, 5, 3, 1, NOW() - INTERVAL 12 HOUR);

INSERT INTO point_transactions (user_id, type, points, reason, survey_id, related_user_id, created_at) VALUES
  (1, 'credit', 50, 'admin_adjustment', NULL, NULL, NOW() - INTERVAL 10 DAY),

  (2, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 9 DAY),
  (2, 'debit', 25, 'survey_publish_budget', 1, NULL, NOW() - INTERVAL 8 DAY),
  (2, 'credit', 2, 'survey_completion_reward', 2, 3, NOW() - INTERVAL 3 DAY),

  (3, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 9 DAY),
  (3, 'debit', 15, 'survey_publish_budget', 2, NULL, NOW() - INTERVAL 6 DAY),
  (3, 'credit', 3, 'survey_completion_reward', 1, 2, NOW() - INTERVAL 5 DAY),
  (3, 'credit', 1, 'survey_completion_reward', 5, 1, NOW() - INTERVAL 12 HOUR),

  (4, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 9 DAY),
  (4, 'debit', 6, 'survey_publish_budget', 3, NULL, NOW() - INTERVAL 2 DAY),
  (4, 'credit', 3, 'survey_completion_reward', 1, 2, NOW() - INTERVAL 4 DAY),

  (5, 'credit', 5, 'starter_bonus', NULL, NULL, NOW() - INTERVAL 9 DAY),
  (5, 'debit', 13, 'survey_publish_budget', 4, NULL, NOW() - INTERVAL 1 DAY),
  (5, 'credit', 3, 'survey_completion_reward', 1, 2, NOW() - INTERVAL 2 DAY),
  (5, 'credit', 2, 'survey_completion_reward', 2, 3, NOW() - INTERVAL 1 DAY);

ALTER TABLE users AUTO_INCREMENT = 6;
ALTER TABLE surveys AUTO_INCREMENT = 6;
ALTER TABLE survey_completions AUTO_INCREMENT = 7;
