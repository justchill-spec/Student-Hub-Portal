<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db.php';

header('Content-Type: text/html; charset=utf-8');

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    $pdo = getDatabaseConnection();

    $counts = [];
    foreach (['students', 'events', 'registrations'] as $table) {
        $statement = $pdo->query('SELECT COUNT(*) FROM ' . $table);
        $counts[$table] = (int) $statement->fetchColumn();
    }

    $studentCode = 'STU001';
    $statement = $pdo->prepare(
        'SELECT s.student_code, s.full_name, d.department_name
         FROM students AS s
         INNER JOIN departments AS d ON d.department_id = s.department_id
         WHERE s.student_code = :student_code'
    );
    $statement->execute(['student_code' => $studentCode]);
    $student = $statement->fetch();
} catch (PDOException $exception) {
    error_log('StudentHub database test failed: ' . $exception->getMessage());
    http_response_code(500);
    if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
        $error = 'The PDO MySQL driver (pdo_mysql) is not enabled in this PHP runtime.';
    } else {
        $error = 'Database connection or query failed. Check MySQL is running and import database/studenthub.sql. See the PHP error log for details.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Database Test</title>
    <style>
        body { max-width: 760px; margin: 3rem auto; padding: 0 1rem; font: 1rem/1.6 system-ui, sans-serif; color: #172033; }
        .result { padding: 1.5rem; border: 1px solid #cbd5e1; border-radius: 0.75rem; background: #f8fafc; }
        .success { border-color: #16a34a; }
        .error { border-color: #dc2626; }
    </style>
</head>
<body>
    <?php if (isset($error)): ?>
        <main class="result error" role="alert">
            <h1>Database test failed</h1>
            <p><?= escapeHtml($error) ?></p>
        </main>
    <?php else: ?>
        <main class="result success" role="status">
            <h1>Database connection successful</h1>
            <p>Connected to <strong>studenthub_db</strong> using PDO and MySQL.</p>
            <h2>Seeded record counts</h2>
            <ul>
                <?php foreach ($counts as $table => $count): ?>
                    <li><?= escapeHtml(ucfirst($table)) ?>: <?= $count ?></li>
                <?php endforeach; ?>
            </ul>
            <h2>Prepared statement test</h2>
            <?php if ($student !== false): ?>
                <p><?= escapeHtml($student['student_code']) ?> — <?= escapeHtml($student['full_name']) ?> (<?= escapeHtml($student['department_name']) ?>)</p>
            <?php else: ?>
                <p>No matching sample student found. Re-import the SQL seed data.</p>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</body>
</html>
