# 📦 Local Setup Instructions

When someone clones the repository, they must provide their own credentials. Follow these steps:

## 1. Install Dependencies
- PHP 7.4+ with cURL enabled
- MySQL 5.7+
- Composer / npm only if needed for extra tooling (not required by default)

## 2. Database Setup
1. Create database `prcf_keuangan`.
2. Import `sql/dumps/prcf_keuangan_clean.sql`.

## 3. Configuration Files
1. Copy template files:
   ```bash
   copy includes\config.example.php includes\config.php
   copy includes\config.local.php.example includes\config.local.php
   copy includes\maintenance_config.example.php includes\maintenance_config.php
   ```
2. Open `includes/config.local.php` and set:
   - `SMTP_USER` = Gmail address
   - `SMTP_PASS` = 16-digit App Password (Google Account → Security → App Passwords → Mail → Other)
   - `FROM_EMAIL` = same Gmail address
   - `EMAIL_OTP_ENABLED` = true

3. (Optional) In `config.local.php`, override database credentials or other constants.

> `config.local.php` is ignored by git so secrets stay local.

## 4. Maintenance Config
- `maintenance_config.php` currently keeps maintenance off; adjust as needed with your IP whitelist.

## 5. Running
1. Start Apache & MySQL (e.g., via XAMPP).
2. Visit `http://localhost/prcf_keuangan_dashboard/index.php`.
3. Login credentials are seeded in the database (see README for default emails/passwords).

## 6. Email OTP Testing
- Use login flow to trigger OTP. Check Gmail inbox for the message (HTML template includes the 6-digit code).
- If emails are blank or not sent:
  - Ensure App Password is correct
  - Check `php_error.log` for SMTP debug output
  - Verify port 587 is open and not blocked by a firewall

## 7. Developer Mode (optional)
Turn on OTP bypass for rapid testing in `includes/config.php`:
```php
define('DEVELOPER_MODE', true);
define('SKIP_OTP_FOR_ALL', true);
```

## 8. Pushing Changes
Before pushing, make sure secrets are not committed:
- Keep real credentials in `config.local.php` only
- Review `git status` to ensure `includes/config.php` only contains placeholders/template data

Happy hacking! 🎉
