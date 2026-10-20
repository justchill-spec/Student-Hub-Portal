# Practical 9: Secure User Registration

## Implementation

The registration form in [`../html/register.html`](../html/register.html) submits by POST to [`../register_process.php`](../register_process.php). JavaScript provides immediate field validation; PHP independently validates all submitted values before database access. The student ID is the unique username.

The handler uses MySQLi prepared statements for duplicate detection and insertion, stores passwords using `password_hash()` with `PASSWORD_DEFAULT`, and relies on unique database constraints for both username and email. Duplicate entries receive an HTTP 409 response; invalid fields receive HTTP 422. Passwords are not saved to browser `localStorage`. The database connection uses the same `STUDENTHUB_DB_*` environment variables and local XAMPP defaults as Practical 8.

The `users` table is included in [`../database/studenthub.sql`](../database/studenthub.sql). Import or re-import that SQL file to create/update the table.

## Run and verify

1. Start MySQL in XAMPP. Run the project server using XAMPP PHP from the project root: `C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .`.
2. Visit `http://127.0.0.1:8000/html/register.html`.
3. Submit valid sample values: name `Test Student`, student ID `P9TEST001`, email `p9test@example.com`, mobile `9876543210`, password `Secure@123`, confirm the same password, select any listed course/year/gender, and accept the terms.
4. A successful request displays a confirmation page. Submitting the same student ID or email again displays a duplicate-account error.
5. In phpMyAdmin, inspect `studenthub_db.users`. The `password_hash` column should contain a hash, not the submitted password.

The registration page and handler enforce the same name, student ID, email, mobile, password-strength/length, course, year, gender, confirmation, and terms requirements. The server remains authoritative even when client-side validation is bypassed.
