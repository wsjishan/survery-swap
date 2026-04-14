# SurveySwap Route Map

| File Route | Method | Access | Purpose |
|---|---|---|---|
| `/index.php` | GET | Public | Landing page |
| `/register.php` | GET | Guest only | Registration form |
| `/actions/register_action.php` | POST | Guest only | Create user + starter points transaction |
| `/login.php` | GET | Guest only | Login form |
| `/actions/login_action.php` | POST | Guest only | Authenticate user |
| `/logout.php` | GET/POST | Logged-in | End session |
| `/dashboard.php` | GET | Logged-in | Legacy route; redirects to `/surveys.php` (or admin dashboard for admins) |
| `/surveys.php` | GET | Logged-in | Browse published surveys |
| `/survey-details.php?id={id}` | GET | Logged-in | Survey details and completion action |
| `/actions/complete_survey_action.php` | POST | Logged-in | Mark completion and award points |
| `/submit-survey.php` | GET | Logged-in | Submit survey form |
| `/actions/submit_survey_action.php` | POST | Logged-in | Create published survey and deduct points |
| `/my-surveys.php` | GET | Logged-in | Current user's submitted surveys |
| `/completed-surveys.php` | GET | Logged-in | Current user's completed surveys |
| `/admin/dashboard.php` | GET | Admin only | Admin metrics dashboard |
| `/admin/moderation.php` | GET | Admin only | Pending survey moderation |
| `/actions/approve_survey_action.php` | POST | Admin only | Approve pending survey |
| `/actions/reject_survey_action.php` | POST | Admin only | Reject pending survey |
