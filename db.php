<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    $host = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
    $port = getenv('STUDENTHUB_DB_PORT') ?: '3306';
    $database = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub_db';
    $username = getenv('STUDENTHUB_DB_USER') ?: 'root';
    $password = getenv('STUDENTHUB_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
