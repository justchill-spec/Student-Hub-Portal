# Practical 12: Event Management CRUD with Poster Upload

## Features

- Admin-only event management page with create, view, update, and delete actions using prepared MySQLi statements.
- Event status management (`Draft`, `Published`, `Cancelled`). Only published events are returned to the public events page.
- Poster upload accepts validated JPEG, PNG, and WebP images up to 5 MB. MIME type and image data are checked server-side, and files receive random server-generated names.
- CSRF protection, output escaping, duplicate event-code feedback, upload/CRUD success and failure messages, and delete confirmation are included.
- Events with existing registrations cannot be deleted because of the database foreign key; the admin is prompted to cancel the event instead.

## Setup

For a new database, import [`../database/studenthub.sql`](../database/studenthub.sql) in phpMyAdmin. For an existing StudentHub database, import [`../database/practical12_setup.sql`](../database/practical12_setup.sql) to add the poster and status columns.

Make sure the project’s `uploads/event-posters` folder is writable by Apache. The folder disables directory listing and denies PHP script access. Start XAMPP Apache and MySQL, sign in with an administrator account, and use **Manage events** on the admin dashboard.

For the full 5 MB application limit, set PHP’s `upload_max_filesize` to at least `5M` and `post_max_size` to a value larger than `5M` (for example, `8M`) in `php.ini`, then restart Apache.

The public events page (`html/events.html`) now retrieves published events from `events_api.php`; its search and sort controls continue to work, and uploaded posters are displayed on the event cards.

## Test cases

| Test | Action | Expected result |
| --- | --- | --- |
| Read | Open Manage events | Event records, category, status, and poster availability are listed |
| Create | Add a valid event without a poster | Event is saved and a success message appears |
| Poster upload | Add or edit an event with a valid JPEG, PNG, or WebP image no larger than 5 MB | Poster is stored with a generated filename and linked to the event |
| Invalid file | Upload a text file renamed with an image extension or an unsupported image format | Upload is rejected with a clear validation message |
| Size validation | Upload an image larger than 5 MB | Upload is rejected |
| Update | Edit event details and save | Updated information appears with a success message |
| Replace poster | Edit an event and upload a new valid poster | New poster replaces the old one |
| Remove poster | Edit and select Remove current poster | Poster is removed from the event |
| Publish status | Set an event to Draft or Cancelled | It no longer appears in the public event list |
| Public read | Publish an event and open the public events page | It is returned by the database-backed API |
| Delete | Delete an unregistered event and confirm | Event and its managed poster are removed |
| Delete registered event | Try to delete an event with registrations | Delete is rejected; event can instead be marked Cancelled |
| Authorization | Open the management page without an admin role | Authentication/role protection redirects the request |

Capture screenshots locally of the event list, valid poster upload, validation failure, and public event card. Submit them with the SQL dump and completed test results.
