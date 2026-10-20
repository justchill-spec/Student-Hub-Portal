<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'auth.php';
$user = auth_require_role('student');
define('STUDENTHUB_DASHBOARD_RENDER', true);
?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'dashboard_content.php'; ?>
