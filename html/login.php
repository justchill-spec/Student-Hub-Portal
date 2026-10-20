<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'auth.php';
auth_start();
if (auth_user() !== null) {
    auth_redirect_by_role();
}

$csrfToken = auth_csrf_token();
$messages = [
    'failed' => 'Invalid username/email or password.',
    'timeout' => 'Your session expired after inactivity. Please sign in again.',
    'unauthorized' => 'Please sign in to access that page.',
    'logout' => 'You have been logged out successfully.',
    'unavailable' => 'Login is temporarily unavailable. Please try again later.',
];
$reason = isset($_GET['reason']) && is_string($_GET['reason']) ? $_GET['reason'] : '';
$message = $messages[$reason] ?? (!empty($GLOBALS['auth_timed_out']) ? $messages['timeout'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <header>
        <img src="../images/logo.png" alt="Studenthub logo" style="width: 100px; height: 100px;">
        <h1 style="text-align: center;">Student Hub Portal</h1>
    </header>
    <div class="login-container">
        <h2 style="text-align: center; margin-bottom: 1.5rem; color: #35b0e4;">StudentHub Login</h2>
        <?php if ($message !== ''): ?>
            <p class="error-message" role="<?= $reason === 'logout' ? 'status' : 'alert' ?>" style="text-align: center;"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endif; ?>
        <form id="loginForm" action="../login_process.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <div class="form-group">
                <label for="login">Student ID, username, or email</label>
                <input type="text" id="login" name="login" maxlength="254" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" maxlength="72" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="remember" value="1"> Remember me for 30 days on this device</label>
            </div>
            <button type="submit" class="submit-btn">Login</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;">Don't have an account? <a href="register.html">Register</a></p>
        <p style="text-align: center;"><a href="../index.html">Back to portal home</a></p>
    </div>
</body>
</html>
