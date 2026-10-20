<?php
declare(strict_types=1);

const AUTH_SESSION_TIMEOUT = 900;
const AUTH_REMEMBER_COOKIE = 'studenthub_remember';
const AUTH_REMEMBER_LIFETIME = 2592000;

function auth_database(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $host = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('STUDENTHUB_DB_PORT') ?: '3306');
    $database = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub_db';
    $username = getenv('STUDENTHUB_DB_USER') ?: 'root';
    $password = getenv('STUDENTHUB_DB_PASSWORD');

    $connection = new mysqli($host, $username, $password === false ? '' : $password, $database, $port);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function auth_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_set_remember_cookie(string $value, int $expires): void
{
    setcookie(AUTH_REMEMBER_COOKIE, $value, auth_cookie_options($expires));
    $_COOKIE[AUTH_REMEMBER_COOKIE] = $value;
}

function auth_revoke_remember_cookie(): void
{
    $cookie = $_COOKIE[AUTH_REMEMBER_COOKIE] ?? '';
    if (is_string($cookie) && preg_match('/^([a-f0-9]{24}):[a-f0-9]{64}$/', $cookie, $matches)) {
        try {
            $connection = auth_database();
            $statement = $connection->prepare('DELETE FROM remember_tokens WHERE selector = ?');
            $statement->bind_param('s', $matches[1]);
            $statement->execute();
            $statement->close();
            $connection->close();
        } catch (mysqli_sql_exception $exception) {
            error_log('StudentHub remember-token revocation failed: ' . $exception->getMessage());
        }
    }

    setcookie(AUTH_REMEMBER_COOKIE, '', auth_cookie_options(time() - 3600));
    unset($_COOKIE[AUTH_REMEMBER_COOKIE]);
}

function auth_destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', auth_cookie_options(time() - 3600));
    }
    session_destroy();
}

function auth_restore_remembered_user(): bool
{
    $cookie = $_COOKIE[AUTH_REMEMBER_COOKIE] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $matches)) {
        return false;
    }

    try {
        $connection = auth_database();
        $statement = $connection->prepare(
            'SELECT t.token_hash, t.user_id, u.username, u.full_name, u.email, u.role
             FROM remember_tokens AS t
             INNER JOIN users AS u ON u.user_id = t.user_id
             WHERE t.selector = ? AND t.expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $statement->bind_param('s', $matches[1]);
        $statement->execute();
        $rememberedUser = $statement->get_result()->fetch_assoc();
        $statement->close();

        if ($rememberedUser === null || !hash_equals($rememberedUser['token_hash'], hash('sha256', $matches[2]))) {
            $connection->close();
            auth_revoke_remember_cookie();
            return false;
        }

        $newToken = bin2hex(random_bytes(32));
        $newHash = hash('sha256', $newToken);
        $rotate = $connection->prepare(
            'UPDATE remember_tokens
             SET token_hash = ?, expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY)
             WHERE selector = ?'
        );
        $rotate->bind_param('ss', $newHash, $matches[1]);
        $rotate->execute();
        $rotate->close();
        $connection->close();

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $rememberedUser['user_id'],
            'username' => $rememberedUser['username'],
            'full_name' => $rememberedUser['full_name'],
            'email' => $rememberedUser['email'],
            'role' => $rememberedUser['role'],
        ];
        $_SESSION['last_activity'] = time();
        auth_set_remember_cookie($matches[1] . ':' . $newToken, time() + AUTH_REMEMBER_LIFETIME);

        return true;
    } catch (mysqli_sql_exception $exception) {
        error_log('StudentHub remember-token restoration failed: ' . $exception->getMessage());
        auth_revoke_remember_cookie();
        return false;
    }
}

function auth_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $GLOBALS['auth_timed_out'] = false;
    if (isset($_SESSION['user'])) {
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
        if ($lastActivity === 0 || time() - $lastActivity > AUTH_SESSION_TIMEOUT) {
            auth_revoke_remember_cookie();
            auth_destroy_session();
            unset($_COOKIE[session_name()]);
            session_id('');
            session_start();
            $GLOBALS['auth_timed_out'] = true;
        } else {
            $_SESSION['last_activity'] = time();
            return;
        }
    }

    if (!auth_restore_remembered_user()) {
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    }
}

function auth_csrf_token(): string
{
    auth_start();
    $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function auth_csrf_is_valid(mixed $token): bool
{
    auth_start();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function auth_user(): ?array
{
    auth_start();
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function auth_role_url(string $role): string
{
    return $role === 'admin' ? 'admin_dashboard.php' : 'html/dashboard.php';
}

function auth_redirect_by_role(): never
{
    $user = auth_user();
    if ($user !== null) {
        $destination = $user['role'] === 'admin' ? '../admin_dashboard.php' : 'dashboard.php';
        header('Location: ' . $destination, true, 303);
        exit;
    }
    header('Location: html/login.php', true, 303);
    exit;
}

function auth_require_role(string $role): array
{
    $user = auth_user();
    if ($user === null) {
        $reason = !empty($GLOBALS['auth_timed_out']) ? 'timeout' : 'unauthorized';
        header('Location: login.php?reason=' . $reason, true, 303);
        exit;
    }

    if ($user['role'] !== $role) {
        $isHtmlRoute = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/html/');
        $destination = $isHtmlRoute && $user['role'] === 'admin'
            ? '../admin_dashboard.php'
            : auth_role_url($user['role']);
        header('Location: ' . $destination, true, 303);
        exit;
    }

    return $user;
}

function auth_create_remember_token(mysqli $connection, int $userId): void
{
    $selector = bin2hex(random_bytes(12));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $statement = $connection->prepare(
        'INSERT INTO remember_tokens (selector, user_id, token_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))'
    );
    $statement->bind_param('sis', $selector, $userId, $tokenHash);
    $statement->execute();
    $statement->close();
    auth_set_remember_cookie($selector . ':' . $token, time() + AUTH_REMEMBER_LIFETIME);
}
