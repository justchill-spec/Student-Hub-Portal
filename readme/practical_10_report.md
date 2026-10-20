# Practical 10: Secure Login, Logout, Sessions, Roles, Timeout, and Remember Me

## Features

- Login accepts a username/student ID or email and verifies the stored hash with `password_verify()`.
- Successful authentication regenerates the PHP session ID. Sessions use strict mode, HTTP-only/SameSite cookies, no-cache responses, and a 15-minute inactivity timeout.
- Student and administrator routes enforce the corresponding role. Unauthenticated requests return to the secure login page; wrong-role requests are redirected to that role's dashboard.
- Logout is a POST protected by a CSRF token. It revokes any remember token and destroys the session.
- Optional Remember Me issues a random selector/token cookie with `HttpOnly`, `SameSite=Lax`, and `Secure` when HTTPS is active. Only a SHA-256 token hash is stored in `remember_tokens`; remembered tokens are rotated when used and expire after 30 days.
- Registration creates student-role accounts; passwords continue to be stored as `password_hash()` output.

## Setup

The Practical 9 database already has `users`. Apply [`../database/practical10_setup.sql`](../database/practical10_setup.sql) **once** in phpMyAdmin (select Import and run the file) to add roles, the remember-token table, and two demonstration accounts. The full [`../database/studenthub.sql`](../database/studenthub.sql) dump creates these tables and seeds the same accounts for a new database installation.

Start XAMPP MySQL and run the application with XAMPP's PHP from the project root:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Login page: `http://127.0.0.1:8000/html/login.php`

| Role | Username | Email | Password |
| --- | --- | --- | --- |
| Admin | `ADMIN` | `admin@studenthub.edu` | `Admin@StudentHub2026` |
| Student | `STUDEMO01` | `student.demo@studenthub.edu` | `Student@2026` |

These are demonstration-only credentials for a local lab; replace them before deployment.

## Verification / screenshots

1. **Successful login:** sign in with either sample account; capture the role-appropriate dashboard.
2. **Failed login:** submit a wrong password; the page displays a generic credential error.
3. **Logout:** click **Log out** on the dashboard; capture the confirmation on the login page.
4. **Timeout:** leave a signed-in session idle for 15 minutes, then request the dashboard. It should redirect to login with a timeout notice.
5. **Unauthorized access:** in a private/incognito window, open `http://127.0.0.1:8000/html/dashboard.php`; it redirects to login. Signing in as a student and manually opening `http://127.0.0.1:8000/admin_dashboard.php` redirects back to the student dashboard.
6. **Remember me:** check the option during login. The browser stores an HTTP-only random token cookie; the database stores only its hash.

For a functional session-timeout test without waiting 15 minutes, change `AUTH_SESSION_TIMEOUT` in `auth.php` temporarily to `10`, test after 10 seconds of inactivity, then restore it to `900`.
