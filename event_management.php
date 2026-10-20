<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
auth_require_role('admin');

const EVENT_POSTER_MAX_BYTES = 5242880;
const EVENT_POSTER_DIRECTORY = 'uploads' . DIRECTORY_SEPARATOR . 'event-posters';

function eventManagementEscape(string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function eventManagementPostString(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? trim($value) : null;
}

function eventManagementDeletePoster(?string $posterPath): void
{
    if ($posterPath === null || !preg_match('#^uploads/event-posters/[a-f0-9]{32}\.(jpg|png|webp)$#', $posterPath)) {
        return;
    }

    $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $posterPath);
    if (is_file($filePath) && !unlink($filePath)) {
        error_log('StudentHub could not remove event poster: ' . $filePath);
    }
}

function eventManagementUploadPoster(): ?string
{
    $file = $_FILES['poster'] ?? null;
    if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
        throw new RuntimeException('The poster upload did not contain valid file information.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
            $message = 'The server rejected this upload size. Posters may be up to 5 MB; check upload_max_filesize and post_max_size in php.ini.';
        } elseif ($file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $message = 'Poster exceeds the 5 MB upload limit.';
        } else {
            $message = 'Poster upload failed. Please choose the file again.';
        }
        throw new RuntimeException($message);
    }
    if (!isset($file['tmp_name'], $file['size'], $file['name'])
        || !is_string($file['tmp_name'])
        || !is_int($file['size'])
        || !is_string($file['name'])
        || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded poster could not be verified.');
    }
    if ($file['size'] < 1 || $file['size'] > EVENT_POSTER_MAX_BYTES) {
        throw new RuntimeException('Poster must be a non-empty image no larger than 5 MB.');
    }

    $temporarySize = filesize($file['tmp_name']);
    if ($temporarySize === false || $temporarySize > EVENT_POSTER_MAX_BYTES) {
        throw new RuntimeException('Poster must be no larger than 5 MB.');
    }

    $image = @getimagesize($file['tmp_name']);
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if ($image === false || !isset($allowedTypes[$mimeType]) || ($image['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('Poster must be a valid JPEG, PNG, or WebP image.');
    }

    $directory = __DIR__ . DIRECTORY_SEPARATOR . EVENT_POSTER_DIRECTORY;
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Poster storage is unavailable. Check the uploads/event-posters folder permissions.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
    $relativePath = 'uploads/event-posters/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The poster could not be saved. Check the uploads/event-posters folder permissions.');
    }

    return $relativePath;
}

$connection = null;
$databaseError = null;
$formError = null;
$flash = null;
$categories = [];
$events = [];
$editingEvent = null;
$formValues = [];
$newPosterPath = null;

try {
    $connection = auth_database();
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub event-management connection failed: ' . $exception->getMessage());
    $databaseError = 'Event records are unavailable right now. Check the database connection and try again.';
}

if (isset($_SESSION['event_management_flash']) && is_array($_SESSION['event_management_flash'])) {
    $flash = $_SESSION['event_management_flash'];
    unset($_SESSION['event_management_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $_SESSION['event_management_flash'] = [
            'type' => 'error',
            'message' => 'Your session token is invalid or expired. Please try the action again.',
        ];
        header('Location: event_management.php', true, 303);
        exit;
    }

    $action = eventManagementPostString('action');
    $eventId = filter_var(eventManagementPostString('event_id') ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($connection === null) {
        $formError = $databaseError ?? 'The database connection is unavailable.';
    } elseif ($action === 'delete') {
        if ($eventId === false) {
            $formError = 'Select a valid event record to delete.';
        } else {
            try {
                $lookup = $connection->prepare('SELECT poster_path FROM events WHERE event_id = ?');
                $lookup->bind_param('i', $eventId);
                $lookup->execute();
                $existing = $lookup->get_result()->fetch_assoc();
                $lookup->close();

                if ($existing === null) {
                    $_SESSION['event_management_flash'] = ['type' => 'error', 'message' => 'The event record was not found.'];
                } else {
                    $statement = $connection->prepare('DELETE FROM events WHERE event_id = ?');
                    $statement->bind_param('i', $eventId);
                    $statement->execute();
                    $deleted = $statement->affected_rows;
                    $statement->close();
                    if ($deleted === 1) {
                        eventManagementDeletePoster($existing['poster_path']);
                        $_SESSION['event_management_flash'] = ['type' => 'success', 'message' => 'Event deleted successfully.'];
                    } else {
                        $_SESSION['event_management_flash'] = ['type' => 'error', 'message' => 'No event record was deleted.'];
                    }
                }
                header('Location: event_management.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $exception) {
                error_log('StudentHub event deletion failed: ' . $exception->getMessage());
                $formError = (int) $exception->getCode() === 1451
                    ? 'This event has registrations and cannot be deleted. Set its status to Cancelled instead.'
                    : 'The event could not be deleted. Please try again.';
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        $formValues = [
            'event_id' => eventManagementPostString('event_id') ?? '',
            'event_code' => strtoupper(eventManagementPostString('event_code') ?? ''),
            'title' => eventManagementPostString('title') ?? '',
            'category_id' => eventManagementPostString('category_id') ?? '',
            'event_date' => eventManagementPostString('event_date') ?? '',
            'event_time' => eventManagementPostString('event_time') ?? '',
            'venue' => eventManagementPostString('venue') ?? '',
            'organizer' => eventManagementPostString('organizer') ?? '',
            'seat_capacity' => eventManagementPostString('seat_capacity') ?? '',
            'description' => eventManagementPostString('description') ?? '',
            'status' => eventManagementPostString('status') ?? 'Draft',
            'poster_path' => '',
        ];
        $categoryId = filter_var($formValues['category_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $seatCapacity = filter_var($formValues['seat_capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $formValues['event_date']);
        $dateValid = $date !== false && $date->format('Y-m-d') === $formValues['event_date'];

        if (!preg_match('/^[A-Z0-9_-]{3,20}$/', $formValues['event_code'])) {
            $formError = 'Event code must be 3-20 letters, numbers, hyphens, or underscores.';
        } elseif ($formValues['title'] === '' || mb_strlen($formValues['title']) > 150) {
            $formError = 'Event title is required and must be 150 characters or fewer.';
        } elseif ($categoryId === false) {
            $formError = 'Select a valid event category.';
        } elseif (!$dateValid) {
            $formError = 'Enter a valid event date.';
        } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $formValues['event_time'])) {
            $formError = 'Enter a valid event time.';
        } elseif ($formValues['venue'] === '' || mb_strlen($formValues['venue']) > 150) {
            $formError = 'Venue is required and must be 150 characters or fewer.';
        } elseif ($formValues['organizer'] === '' || mb_strlen($formValues['organizer']) > 120) {
            $formError = 'Organizer is required and must be 120 characters or fewer.';
        } elseif ($seatCapacity === false) {
            $formError = 'Seat capacity must be a whole number between 1 and 65,535.';
        } elseif ($formValues['description'] === '' || mb_strlen($formValues['description']) > 5000) {
            $formError = 'Description is required and must be 5,000 characters or fewer.';
        } elseif (!in_array($formValues['status'], ['Draft', 'Published', 'Cancelled'], true)) {
            $formError = 'Select a valid event status.';
        } elseif ($action === 'update' && $eventId === false) {
            $formError = 'Select a valid event record to update.';
        } else {
            try {
                if ($action === 'update') {
                    $existingStatement = $connection->prepare('SELECT poster_path FROM events WHERE event_id = ?');
                    $existingStatement->bind_param('i', $eventId);
                    $existingStatement->execute();
                    $existingEvent = $existingStatement->get_result()->fetch_assoc();
                    $existingStatement->close();
                    if ($existingEvent === null) {
                        throw new RuntimeException('The event record to update was not found.');
                    }
                    $formValues['poster_path'] = $existingEvent['poster_path'] ?? '';
                }

                $categoryCheck = $connection->prepare('SELECT category_id FROM event_categories WHERE category_id = ?');
                $categoryCheck->bind_param('i', $categoryId);
                $categoryCheck->execute();
                $categoryExists = $categoryCheck->get_result()->num_rows === 1;
                $categoryCheck->close();
                if (!$categoryExists) {
                    throw new RuntimeException('Select an available event category.');
                }

                $newPosterPath = eventManagementUploadPoster();
                $posterPath = $newPosterPath ?? $formValues['poster_path'];
                if ($newPosterPath === null
                    && eventManagementPostString('remove_poster') === '1') {
                    $posterPath = '';
                }
                $posterPathValue = $posterPath !== '' ? $posterPath : null;
                $eventTime = $formValues['event_time'] . ':00';

                if ($action === 'create') {
                    $statement = $connection->prepare(
                        'INSERT INTO events
                            (event_code, title, category_id, event_date, event_time, venue, organizer,
                             seat_capacity, description, poster_path, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param(
                        'ssissssisss',
                        $formValues['event_code'],
                        $formValues['title'],
                        $categoryId,
                        $formValues['event_date'],
                        $eventTime,
                        $formValues['venue'],
                        $formValues['organizer'],
                        $seatCapacity,
                        $formValues['description'],
                        $posterPathValue,
                        $formValues['status']
                    );
                    $statement->execute();
                    $statement->close();
                    $_SESSION['event_management_flash'] = ['type' => 'success', 'message' => 'Event created successfully.'];
                } else {
                    $statement = $connection->prepare(
                        'UPDATE events
                         SET event_code = ?, title = ?, category_id = ?, event_date = ?, event_time = ?,
                             venue = ?, organizer = ?, seat_capacity = ?, description = ?, poster_path = ?, status = ?
                         WHERE event_id = ?'
                    );
                    $statement->bind_param(
                        'ssissssisssi',
                        $formValues['event_code'],
                        $formValues['title'],
                        $categoryId,
                        $formValues['event_date'],
                        $eventTime,
                        $formValues['venue'],
                        $formValues['organizer'],
                        $seatCapacity,
                        $formValues['description'],
                        $posterPathValue,
                        $formValues['status'],
                        $eventId
                    );
                    $statement->execute();
                    $statement->close();
                    $_SESSION['event_management_flash'] = ['type' => 'success', 'message' => 'Event updated successfully.'];
                }

                if ($action === 'update' && ($newPosterPath !== null || $posterPath === '')) {
                    eventManagementDeletePoster($formValues['poster_path']);
                }
                $connection->close();
                header('Location: event_management.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $exception) {
                if ($newPosterPath !== null) {
                    eventManagementDeletePoster($newPosterPath);
                    $newPosterPath = null;
                }
                error_log('StudentHub event save failed: ' . $exception->getMessage());
                $formError = (int) $exception->getCode() === 1062
                    ? 'That event code is already in use.'
                    : 'The event could not be saved. Please check the database and try again.';
            } catch (RuntimeException $exception) {
                if ($newPosterPath !== null) {
                    eventManagementDeletePoster($newPosterPath);
                    $newPosterPath = null;
                }
                $formError = $exception->getMessage();
            }
        }
        $editingEvent = $action === 'update' ? $formValues : null;
    } else {
        $formError = 'Choose a valid event-management action.';
    }
}

$search = $_GET['q'] ?? '';
$search = is_string($search) ? trim($search) : '';
$statusFilter = $_GET['status'] ?? '';
$statusFilter = is_string($statusFilter) ? trim($statusFilter) : '';
$editId = $_GET['edit'] ?? '';

if ($connection !== null) {
    try {
        $categoryResult = $connection->query('SELECT category_id, category_name FROM event_categories ORDER BY category_name');
        $categories = $categoryResult->fetch_all(MYSQLI_ASSOC);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editId !== '') {
            $validatedEditId = is_string($editId)
                ? filter_var($editId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;
            if ($validatedEditId === false) {
                $formError = 'The selected event record is invalid.';
            } else {
                $editStatement = $connection->prepare('SELECT * FROM events WHERE event_id = ?');
                $editStatement->bind_param('i', $validatedEditId);
                $editStatement->execute();
                $editingEvent = $editStatement->get_result()->fetch_assoc() ?: null;
                $editStatement->close();
                if ($editingEvent === null) {
                    $formError = 'The event record to edit was not found.';
                } else {
                    $editingEvent['event_time'] = substr($editingEvent['event_time'], 0, 5);
                }
            }
        }

        $searchPattern = '%' . $search . '%';
        $listStatement = $connection->prepare(
            "SELECT e.event_id, e.event_code, e.title, e.category_id, c.category_name, e.event_date,
                    e.event_time, e.venue, e.organizer, e.seat_capacity, e.description, e.poster_path, e.status
             FROM events AS e
             INNER JOIN event_categories AS c ON c.category_id = e.category_id
             WHERE (? = '' OR e.title LIKE ? OR e.event_code LIKE ? OR e.organizer LIKE ?)
               AND (? = '' OR e.status = ?)
             ORDER BY e.event_date DESC, e.event_time DESC, e.event_id DESC"
        );
        $listStatement->bind_param('ssssss', $search, $searchPattern, $searchPattern, $searchPattern, $statusFilter, $statusFilter);
        $listStatement->execute();
        $events = $listStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        $listStatement->close();
        $connection->close();
    } catch (mysqli_sql_exception $exception) {
        error_log('StudentHub event-management query failed: ' . $exception->getMessage());
        $databaseError = 'Events could not be loaded. Apply the Practical 12 database setup and try again.';
    }
}

$formValues = $formValues !== [] ? $formValues : ($editingEvent ?? []);
$csrfToken = auth_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Student Hub Portal</title>
    <link rel="stylesheet" href="css/event-management.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="admin_dashboard.php">Student Hub Portal</a>
        <nav aria-label="Admin navigation">
            <a href="admin_dashboard.php">Admin dashboard</a>
            <a href="html/events.html">Public events</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= eventManagementEscape($csrfToken) ?>">
                <button class="link-button" type="submit">Log out</button>
            </form>
        </nav>
    </header>
    <main class="page">
        <section class="page-heading">
            <p class="eyebrow">Administrator tools</p>
            <h1>Event management</h1>
            <p>Create and maintain campus events, manage publication status, and upload event posters.</p>
        </section>

        <?php if ($flash !== null): ?>
            <p class="notice <?= eventManagementEscape($flash['type'] ?? 'error') ?>" role="<?= ($flash['type'] ?? '') === 'success' ? 'status' : 'alert' ?>">
                <?= eventManagementEscape($flash['message'] ?? 'The action could not be completed.') ?>
            </p>
        <?php endif; ?>
        <?php if ($databaseError !== null): ?>
            <p class="notice error" role="alert"><?= eventManagementEscape($databaseError) ?></p>
        <?php endif; ?>
        <?php if ($formError !== null): ?>
            <p class="notice error" role="alert"><?= eventManagementEscape($formError) ?></p>
        <?php endif; ?>

        <section class="panel" aria-labelledby="form-title">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow"><?= $editingEvent !== null ? 'Edit event' : 'New event' ?></p>
                    <h2 id="form-title"><?= $editingEvent !== null ? 'Update event' : 'Add an event' ?></h2>
                </div>
                <?php if ($editingEvent !== null): ?>
                    <a class="secondary-button" href="event_management.php">Cancel edit</a>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data" class="event-form">
                <input type="hidden" name="csrf_token" value="<?= eventManagementEscape($csrfToken) ?>">
                <input type="hidden" name="action" value="<?= $editingEvent !== null ? 'update' : 'create' ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= EVENT_POSTER_MAX_BYTES ?>">
                <?php if ($editingEvent !== null): ?>
                    <input type="hidden" name="event_id" value="<?= eventManagementEscape($formValues['event_id'] ?? '') ?>">
                <?php endif; ?>
                <label>
                    Event code
                    <input name="event_code" maxlength="20" required value="<?= eventManagementEscape($formValues['event_code'] ?? '') ?>">
                </label>
                <label>
                    Title
                    <input name="title" maxlength="150" required value="<?= eventManagementEscape($formValues['title'] ?? '') ?>">
                </label>
                <label>
                    Category
                    <select name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= eventManagementEscape($category['category_id']) ?>" <?= (string) ($formValues['category_id'] ?? '') === (string) $category['category_id'] ? 'selected' : '' ?>>
                                <?= eventManagementEscape($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Date
                    <input type="date" name="event_date" required value="<?= eventManagementEscape($formValues['event_date'] ?? '') ?>">
                </label>
                <label>
                    Time
                    <input type="time" name="event_time" required value="<?= eventManagementEscape(substr((string) ($formValues['event_time'] ?? ''), 0, 5)) ?>">
                </label>
                <label>
                    Venue
                    <input name="venue" maxlength="150" required value="<?= eventManagementEscape($formValues['venue'] ?? '') ?>">
                </label>
                <label>
                    Organizer
                    <input name="organizer" maxlength="120" required value="<?= eventManagementEscape($formValues['organizer'] ?? '') ?>">
                </label>
                <label>
                    Seat capacity
                    <input type="number" name="seat_capacity" min="1" max="65535" required value="<?= eventManagementEscape($formValues['seat_capacity'] ?? '') ?>">
                </label>
                <label>
                    Status
                    <select name="status" required>
                        <?php foreach (['Draft', 'Published', 'Cancelled'] as $status): ?>
                            <option value="<?= eventManagementEscape($status) ?>" <?= ($formValues['status'] ?? 'Draft') === $status ? 'selected' : '' ?>>
                                <?= eventManagementEscape($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="wide-field">
                    Description
                    <textarea name="description" rows="4" maxlength="5000" required><?= eventManagementEscape($formValues['description'] ?? '') ?></textarea>
                </label>
                <label class="wide-field">
                    Poster (JPEG, PNG, or WebP; maximum 5 MB)
                    <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
                </label>
                <?php if (($formValues['poster_path'] ?? '') !== ''): ?>
                    <div class="current-poster">
                        <img src="<?= eventManagementEscape($formValues['poster_path']) ?>" alt="Current event poster">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remove_poster" value="1">
                            Remove current poster
                        </label>
                    </div>
                <?php endif; ?>
                <p class="help-text wide-field">Uploading a new poster replaces the current one. Leave the file field empty to keep it.</p>
                <div class="form-actions wide-field">
                    <button class="primary-button" type="submit"><?= $editingEvent !== null ? 'Save changes' : 'Add event' ?></button>
                </div>
            </form>
        </section>

        <section class="panel" aria-labelledby="events-title">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Event directory</p>
                    <h2 id="events-title">Events <span class="record-count"><?= count($events) ?></span></h2>
                </div>
            </div>
            <form method="get" class="filter-form">
                <label>
                    Search title, code, or organizer
                    <input type="search" name="q" value="<?= eventManagementEscape($search) ?>" placeholder="Search events">
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['Draft', 'Published', 'Cancelled'] as $status): ?>
                            <option value="<?= eventManagementEscape($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                <?= eventManagementEscape($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="primary-button" type="submit">Filter</button>
                    <a class="secondary-button" href="event_management.php">Clear</a>
                </div>
            </form>
            <?php if ($events === []): ?>
                <p class="empty-state">No events match the selected search and filter.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Event</th>
                                <th scope="col">Date / time</th>
                                <th scope="col">Category</th>
                                <th scope="col">Organizer</th>
                                <th scope="col">Status</th>
                                <th scope="col">Poster</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td data-label="Event">
                                        <strong><?= eventManagementEscape($event['title']) ?></strong>
                                        <span class="subtext"><?= eventManagementEscape($event['event_code']) ?> · <?= eventManagementEscape($event['venue']) ?></span>
                                    </td>
                                    <td data-label="Date / time">
                                        <?= eventManagementEscape($event['event_date']) ?>
                                        <span class="subtext"><?= eventManagementEscape(substr($event['event_time'], 0, 5)) ?></span>
                                    </td>
                                    <td data-label="Category"><?= eventManagementEscape($event['category_name']) ?></td>
                                    <td data-label="Organizer"><?= eventManagementEscape($event['organizer']) ?></td>
                                    <td data-label="Status"><span class="status <?= strtolower(eventManagementEscape($event['status'])) ?>"><?= eventManagementEscape($event['status']) ?></span></td>
                                    <td data-label="Poster">
                                        <?php if (!empty($event['poster_path'])): ?>
                                            <a href="<?= eventManagementEscape($event['poster_path']) ?>" target="_blank" rel="noopener">View poster</a>
                                        <?php else: ?>
                                            <span class="subtext">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions" class="row-actions">
                                        <a class="secondary-button" href="event_management.php?edit=<?= eventManagementEscape($event['event_id']) ?>">Edit</a>
                                        <form method="post" onsubmit="return confirm('Delete <?= eventManagementEscape($event['event_code']) ?>? This action cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?= eventManagementEscape($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?= eventManagementEscape($event['event_id']) ?>">
                                            <button class="danger-button" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
