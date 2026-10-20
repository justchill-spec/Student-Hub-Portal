<?php
declare(strict_types=1);

function cleanField(string $value): string
{
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

    return trim($value ?? '');
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderResult(string $title, string $message, bool $isError, array $errors = [], int $status = 200): void
{
    http_response_code($status);
    $class = $isError ? 'error' : 'success';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= escapeHtml($title) ?> - Student Hub Portal</title>
        <link rel="stylesheet" href="css/contact.css">
    </head>
    <body>
        <main class="submission-result <?= $class ?>" aria-labelledby="result-title" <?= $isError ? 'role="alert"' : 'role="status"' ?>>
            <h1 id="result-title"><?= escapeHtml($title) ?></h1>
            <p><?= escapeHtml($message) ?></p>
            <?php if ($errors !== []): ?>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="back-link" href="html/contact.html">Back to contact form</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderResult('Request not accepted', 'Submit the contact form using the form on the contact page.', true, [], 405);
}

$errors = [];
$fields = [];
foreach (['name', 'email', 'message'] as $field) {
    $value = $_POST[$field] ?? null;
    if (!is_string($value)) {
        $errors[] = 'Please provide a valid ' . $field . '.';
        $fields[$field] = '';
        continue;
    }
    $fields[$field] = cleanField($value);
}

if ($fields['name'] === '') {
    $errors[] = 'Name is required.';
} elseif (strlen($fields['name']) > 100) {
    $errors[] = 'Name must be 100 characters or fewer.';
}

if ($fields['email'] === '' || strlen($fields['email']) > 254 || filter_var($fields['email'], FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'Enter a valid email address (254 characters or fewer).';
}

if ($fields['message'] === '') {
    $errors[] = 'Message is required.';
} elseif (strlen($fields['message']) > 5000) {
    $errors[] = 'Message must be 5000 characters or fewer.';
}

if ($errors !== []) {
    renderResult('Please check your submission', 'Your message was not saved. Correct the following and submit the form again.', true, $errors, 422);
}

$storagePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'submissions' . DIRECTORY_SEPARATOR . 'contact_submissions.csv';
$file = @fopen($storagePath, 'c+b');
if ($file === false) {
    renderResult('Unable to save your message', 'The submission file could not be opened. Please try again later.', true, [], 500);
}

if (!flock($file, LOCK_EX)) {
    fclose($file);
    renderResult('Unable to save your message', 'The submission file is temporarily unavailable. Please try again later.', true, [], 500);
}

$saved = false;
if (fseek($file, 0, SEEK_END) === 0) {
    if (ftell($file) === 0) {
        $headerWritten = fputcsv($file, ['submitted_at', 'name', 'email', 'message'], ',', '"', '');
        $saved = $headerWritten !== false;
    } else {
        $saved = true;
    }

    if ($saved) {
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $safeForSpreadsheet = static function (string $value): string {
            return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1 ? "'" . $value : $value;
        };
        $saved = fputcsv($file, [
            $timestamp,
            $safeForSpreadsheet($fields['name']),
            $safeForSpreadsheet($fields['email']),
            $safeForSpreadsheet($fields['message']),
        ], ',', '"', '') !== false && fflush($file);
    }
}

flock($file, LOCK_UN);
fclose($file);

if (!$saved) {
    renderResult('Unable to save your message', 'A file-writing error prevented the submission from being saved. Please try again later.', true, [], 500);
}

renderResult('Message received', 'Thank you. Your message has been submitted to student services.', false);
