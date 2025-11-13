<?php

declare(strict_types=1);

/**
 * Lightweight singleton wrapper around PDO to provide a shared database
 * connection across the application. Update the DSN credentials below or
 * configure environment variables (DB_HOST, DB_NAME, DB_USER, DB_PASS).
 */
final class Database
{
    private static ?\PDO $connection = null;

    private function __construct()
    {
        // Not instantiable.
    }

    public static function getConnection(): \PDO
    {
        if (self::$connection instanceof \PDO) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'university_networking_portal';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $dbname, $charset);

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$connection = new \PDO($dsn, $username, $password, $options);

        return self::$connection;
    }
}

