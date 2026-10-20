# Practical 8: MySQL Schema Design, PDO Connectivity, and Prepared Statements

## Database design

Import [`../database/studenthub.sql`](../database/studenthub.sql) in phpMyAdmin. It creates `studenthub_db` with normalized lookup tables for departments and event categories, plus students, events, and registrations.

- Primary keys identify each row; student and event codes are unique alternate keys.
- Foreign keys link students to departments and registrations to both students and events.
- A unique `(student_id, event_id)` constraint prevents duplicate event registrations.
- InnoDB, `utf8mb4`, field constraints, and indexes are used.
- Seed data contains the 16 students and 16 events from the existing project JSON files and five example registrations.

Relationship summary: one department has many students; one event category has many events; students and events have a many-to-many relationship through registrations.

## PDO connection

[`../db.php`](../db.php) creates a PDO MySQL connection with UTF-8, exception-based error handling, associative fetches, and native prepared statements. It reads settings from these environment variables:

| Variable | Local XAMPP default |
| --- | --- |
| `STUDENTHUB_DB_HOST` | `127.0.0.1` |
| `STUDENTHUB_DB_PORT` | `3306` |
| `STUDENTHUB_DB_NAME` | `studenthub_db` |
| `STUDENTHUB_DB_USER` | `root` |
| `STUDENTHUB_DB_PASSWORD` | empty |

The defaults are intended only for a typical local XAMPP installation. For a deployed app, set the environment variables to a dedicated database account with only the required privileges; do not use the MySQL root account.

## Import and test

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin/`, select **Import**, choose `database/studenthub.sql`, and run the import.
3. If you started PHP's development server instead of XAMPP Apache, start XAMPP's PHP from the project root with `C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .` on Windows. Use a PHP runtime with the `pdo_mysql` extension enabled; MySQL must still be running.
4. Open `http://127.0.0.1:8000/db_test.php` (or `http://localhost/Student-Hub-Portal-main/db_test.php` under XAMPP Apache).
5. Confirm the success message, table counts, and `STU001` prepared-statement result. Capture that page as the connection-test screenshot for submission.

The test page uses a parameterized `SELECT` for the sample student and displays a generic failure message if the connection/query fails; diagnostic details go to the PHP error log.
