<?php

declare(strict_types=1);

function getDbConfig(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/database.php';

        $localPath = __DIR__ . '/database.local.php';
        if (is_file($localPath)) {
            $config = array_merge($config, require $localPath);
        }
    }

    return $config;
}

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = getDbConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}
