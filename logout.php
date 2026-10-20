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
    exit('Invalid request token. Return to your dashboard and try again.');
}

auth_revoke_remember_cookie();
auth_destroy_session();
header('Location: html/login.php?reason=logout', true, 303);
exit;
