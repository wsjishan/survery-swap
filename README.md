# SurveySwap MVP

SurveySwap is a point-based survey exchange platform for students and researchers.

## Tech

- PHP (modular, session-based auth)
- MySQL
- HTML/CSS/JavaScript
- Custom CSS (no Bootstrap)

## Local Setup (XAMPP/WAMP)

1. Place project in `htdocs` (XAMPP) or `www` (WAMP) as `survery-swap`.
2. Create the database schema:
   - Import `database/schema.sql`
   - If upgrading an existing DB, run migration `database/migrations/2026_04_15_native_survey_v1.sql`
3. Seed demo data (optional but recommended):
   - Import `database/seed.sql`
4. Create your environment file:
   - Copy `.env.example` to `.env`
   - Fill in your real DB credentials
5. Recommended: use a dedicated DB user instead of `root`.

### Create Dedicated DB User (recommended)

Run this in MySQL (replace `your_strong_password`):

```sql
CREATE USER 'surveyswap_app'@'localhost' IDENTIFIED BY 'your_strong_password';
CREATE USER 'surveyswap_app'@'127.0.0.1' IDENTIFIED BY 'your_strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON surveyswap.* TO 'surveyswap_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON surveyswap.* TO 'surveyswap_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Then set `.env` like:

```env
APP_ENV=local
BASE_URL_OVERRIDE=
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=surveyswap
DB_USER=surveyswap_app
DB_PASS=your_strong_password
```

6. Visit:
   - `http://localhost/survery-swap/index.php`

## Demo Accounts

(Available only after importing `database/seed.sql`)

- Admin:
  - Email: `admin@surveyswap.test`
  - Password: `Admin@123`
- User (all demo users):
  - Password: `User@1234`

## Notes

- Missing or invalid DB settings now show clear flash errors on register/login/actions.
- Optional route override: set `BASE_URL_OVERRIDE` if your local URL base path is non-standard.
- New surveys are now created as native in-app surveys (V1 supports short-text questions only).
- Legacy Google Form surveys remain supported for existing records.

See [ROUTES.md](./ROUTES.md) for the full route/page map.
