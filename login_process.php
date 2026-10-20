<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
auth_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed');
}

if (!auth_csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token. Return to the login page and try again.');
}

$login = $_POST['login'] ?? null;
$password = $_POST['password'] ?? null;
if (!is_string($login) || trim($login) === '' || !is_string($password) || $password === '' || strlen($password) > 72) {
    header('Location: html/login.php?reason=failed', true, 303);
    exit;
}

try {
    $connection = auth_database();
    $identity = trim($login);
    $statement = $connection->prepare(
        'SELECT user_id, username, full_name, email, password_hash, role
         FROM users
         WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)
         LIMIT 1'
    );
    $statement->bind_param('ss', $identity, $identity);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($user === null || !password_verify($password, $user['password_hash'])) {
        $connection->close();
        header('Location: html/login.php?reason=failed', true, 303);
        exit;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash === false) {
            throw new RuntimeException('Password rehash failed.');
        }
        $rehash = $connection->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $userId = (int) $user['user_id'];
        $rehash->bind_param('si', $newHash, $userId);
        $rehash->execute();
        $rehash->close();
    }

    if (isset($_POST['remember']) && $_POST['remember'] === '1') {
        auth_create_remember_token($connection, (int) $user['user_id']);
    } else {
        auth_revoke_remember_cookie();
    }
    $connection->close();

    session_regenerate_id(true);
    $_SESSION = [
        'user' => [
            'id' => (int) $user['user_id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
        'last_activity' => time(),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];

    header('Location: ' . auth_role_url($user['role']), true, 303);
    exit;
} catch (mysqli_sql_exception | RuntimeException $exception) {
    error_log('StudentHub login failed: ' . $exception->getMessage());
    header('Location: html/login.php?reason=unavailable', true, 303);
    exit;
}
