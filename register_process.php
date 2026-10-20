<?php
declare(strict_types=1);

function registrationEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderRegistrationResult(string $title, string $message, bool $isError, array $errors = [], int $status = 200): void
{
    http_response_code($status);
    $class = $isError ? 'error' : 'success';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= registrationEscape($title) ?> - Student Hub Portal</title>
        <link rel="stylesheet" href="css/register.css">
    </head>
    <body>
        <main class="registration-result <?= $class ?>" role="<?= $isError ? 'alert' : 'status' ?>" aria-labelledby="result-title">
            <h2 id="result-title"><?= registrationEscape($title) ?></h2>
            <p><?= registrationEscape($message) ?></p>
            <?php if ($errors !== []): ?>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= registrationEscape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="back-link" href="html/register.html">Back to registration form</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    renderRegistrationResult('Request not accepted', 'Submit your details using the registration form.', true, [], 405);
}

$errors = [];
$fields = [];
foreach (['fullName', 'studentId', 'email', 'mobile', 'password', 'confirmPassword', 'course', 'year', 'gender'] as $field) {
    $value = $_POST[$field] ?? null;
    if (!is_string($value)) {
        $fields[$field] = '';
        $errors[] = 'Please provide a valid value for ' . $field . '.';
        continue;
    }
    $fields[$field] = $field === 'password' || $field === 'confirmPassword' ? $value : trim($value);
}

$fields['studentId'] = strtoupper($fields['studentId']);
$fields['email'] = strtolower($fields['email']);

if (!preg_match("/^[A-Za-z\\s.'-]{2,50}$/", $fields['fullName'])) {
    $errors[] = 'Full name must be 2-50 characters and contain only letters, spaces, periods, apostrophes, or hyphens.';
}

if (!preg_match('/^[A-Z0-9_-]{3,20}$/', $fields['studentId'])) {
    $errors[] = 'Student ID must be 3-20 alphanumeric characters, hyphens, or underscores.';
}

if (strlen($fields['email']) > 254 || filter_var($fields['email'], FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'Enter a valid email address of 254 characters or fewer.';
}

if (!preg_match('/^[6-9][0-9]{9}$/', $fields['mobile'])) {
    $errors[] = 'Mobile number must be 10 digits starting with 6-9.';
}

if (strlen($fields['password']) > 72 || !preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&#^()_-])[A-Za-z0-9@$!%*?&#^()_-]{8,}$/', $fields['password'])) {
    $errors[] = 'Password must be 8-72 characters and include a letter, a number, and a permitted special character.';
}

if ($fields['confirmPassword'] === '' || !hash_equals($fields['password'], $fields['confirmPassword'])) {
    $errors[] = 'Confirm password must match the password.';
}

$allowedCourses = ['BCA', 'BSc IT', 'BBA', 'MBA', 'MCA', 'BTech Computer Engineering'];
if (!in_array($fields['course'], $allowedCourses, true)) {
    $errors[] = 'Select a valid course.';
}

if (!in_array($fields['year'], ['1', '2', '3', '4'], true)) {
    $errors[] = 'Select a valid year.';
}

if (!in_array($fields['gender'], ['Male', 'Female', 'Other'], true)) {
    $errors[] = 'Select a valid gender.';
}

if (!isset($_POST['terms']) || !is_string($_POST['terms']) || $_POST['terms'] !== 'on') {
    $errors[] = 'You must accept the terms and conditions.';
}

if ($errors !== []) {
    renderRegistrationResult('Please check your details', 'Your account was not created. Correct the following and submit the form again.', true, $errors, 422);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $host = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('STUDENTHUB_DB_PORT') ?: '3306');
    $database = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub_db';
    $username = getenv('STUDENTHUB_DB_USER') ?: 'root';
    $password = getenv('STUDENTHUB_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $mysqli = new mysqli($host, $username, $password, $database, $port);
    $mysqli->set_charset('utf8mb4');

    $check = $mysqli->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->bind_param('ss', $fields['studentId'], $fields['email']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        $mysqli->close();
        renderRegistrationResult('Account already exists', 'That student ID or email is already registered. Use a different student ID or email address.', true, [], 409);
    }
    $check->close();

    $passwordHash = password_hash($fields['password'], PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Password hashing failed.');
    }

    $year = (int) $fields['year'];
    $insert = $mysqli->prepare(
        'INSERT INTO users (username, full_name, email, mobile, password_hash, course, study_year, gender, role)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'student\')'
    );
    $insert->bind_param(
        'ssssssis',
        $fields['studentId'],
        $fields['fullName'],
        $fields['email'],
        $fields['mobile'],
        $passwordHash,
        $fields['course'],
        $year,
        $fields['gender']
    );
    $insert->execute();
    $insert->close();
    $mysqli->close();
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub registration database error: ' . $exception->getMessage());
    if ((int) $exception->getCode() === 1062) {
        renderRegistrationResult('Account already exists', 'That student ID or email is already registered. Use a different student ID or email address.', true, [], 409);
    }
    renderRegistrationResult('Registration unavailable', 'We could not create your account right now. Please try again later.', true, [], 500);
} catch (RuntimeException $exception) {
    error_log('StudentHub registration error: ' . $exception->getMessage());
    renderRegistrationResult('Registration unavailable', 'We could not create your account right now. Please try again later.', true, [], 500);
}

renderRegistrationResult('Registration successful', 'Your account was created securely. Your password is stored as a hash, not as plain text.', false);
