<?php

declare(strict_types=1);

function db_config_missing_keys(): array
{
    $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $missing = [];

    foreach ($required as $key) {
        $value = defined($key) ? trim((string) constant($key)) : '';
        if ($value === '') {
            $missing[] = $key;
        }
    }

    return $missing;
}

function db_user_error_message(Throwable $e): string
{
    $message = $e->getMessage();

    if (str_contains($message, 'Database configuration missing')) {
        return 'Database configuration is missing. Create .env from .env.example and set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.';
    }

    if (str_contains($message, 'Access denied')) {
        return 'Database authentication failed. Check DB_USER and DB_PASS in .env.';
    }

    if (str_contains($message, 'Unknown database')) {
        return 'Database not found. Import database/schema.sql first.';
    }

    if (
        str_contains($message, 'SQLSTATE[HY000] [2002]') ||
        str_contains($message, 'Connection refused') ||
        str_contains($message, 'No such file or directory')
    ) {
        return 'Cannot connect to MySQL. Ensure MySQL is running and DB_HOST/DB_PORT are correct.';
    }

    return 'Database is unavailable right now. Please try again in a moment.';
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $missing = db_config_missing_keys();
    if ($missing !== []) {
        throw new RuntimeException('Database configuration missing: ' . implode(', ', $missing));
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }

    return $pdo;
}
