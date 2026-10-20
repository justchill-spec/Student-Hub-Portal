<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
$user = auth_require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Admin Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header>
        <img src="images/logo.png" alt="Studenthub logo" style="width: 100px; height: 100px;">
        <h1>Student Hub Portal — Admin</h1>
    </header>
    <main class="dashboard">
        <h2>Welcome, <?= htmlspecialchars($user['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
        <p>You are signed in with the administrator role.</p>
        <p><a href="student_management.php">Manage students</a></p>
        <p><a href="event_management.php">Manage events</a></p>
        <form action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <button type="submit">Log out</button>
        </form>
    </main>
</body>
</html>
