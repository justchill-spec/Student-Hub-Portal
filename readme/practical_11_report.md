# Practical 11: Student Management CRUD with Search and Filter

## Features

- Administrator-only student directory with create, read, update, and delete operations using prepared MySQLi statements.
- Search across student name, email, and course, plus a course filter.
- Required field validation, duplicate student ID/email feedback, CSRF protection, escaped output, and confirmation before deleting.
- Success and failure messages for database actions.
- The `students` table includes course data and is seeded with 20 academic records in the full SQL dump.

## Setup

For a new database, import [`../database/studenthub.sql`](../database/studenthub.sql) in phpMyAdmin. For an existing StudentHub database, import [`../database/practical11_setup.sql`](../database/practical11_setup.sql) to add the course column and the remaining four sample records.

The database connection uses the existing `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_PORT`, `STUDENTHUB_DB_NAME`, `STUDENTHUB_DB_USER`, and `STUDENTHUB_DB_PASSWORD` environment variables. Start XAMPP's Apache and MySQL, sign in with an administrator account, then open **Manage students** on the admin dashboard.

## Test cases

| Test | Action | Expected result |
| --- | --- | --- |
| Read | Open the student-management page | Seeded student records, departments, and course values are displayed |
| Create | Add a student with valid values | Record is saved and a success message appears |
| Duplicate validation | Reuse an existing student ID or email | Record is not saved and a duplicate-value message appears |
| Update | Edit a student and save valid changes | Updated values appear in the directory with a success message |
| Search by name | Search for `Aisha` | Matching student records are shown |
| Search by email | Search for `studenthub.edu` | Matching email records are shown |
| Search by course | Search for `BSc IT` | Matching course records are shown |
| Filter | Select a course from the filter | Only students in that course are shown |
| Delete | Select Delete and confirm | Record is removed and a success message appears |
| Cancel delete | Select Delete and cancel the browser confirmation | No delete request is submitted |
| Validation | Submit missing or invalid field values | Record is not saved and an explanatory error is shown |
| Authorization | Open the page without an admin session or as a student | Authentication redirects or role protection prevents access |

## Screenshots and submission

Capture screenshots locally of the student list, a successful add or update, search/filter results, and the delete confirmation/result. Submit those screenshots with the SQL dump and completed test-case results.
