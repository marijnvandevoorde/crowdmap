<?php

declare(strict_types=1);

namespace Crowdmap\Database;

/**
 * Builds the database connection from the environment.
 *
 * MySQL is configured with MYSQL_HOST, MYSQL_PORT, MYSQL_DATABASE, MYSQL_USER and
 * MYSQL_PASSWORD (the older DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD still
 * work). DB_TYPE picks the driver; without it, MySQL is used when MYSQL_HOST is set.
 */
final class ConnectionFactory
{
    public static function driver(): string
    {
        $type = self::env('DB_TYPE');
        if ($type !== null) {
            return strtolower($type);
        }

        return self::env('MYSQL_HOST') !== null ? 'mysql' : 'sqlite';
    }

    public static function fromEnvironment(): DatabaseConnection
    {
        if (self::driver() === 'mysql') {
            return new MysqlConnection(
                host: self::env('MYSQL_HOST', 'DB_HOST') ?? 'localhost',
                database: self::env('MYSQL_DATABASE', 'DB_NAME') ?? 'crowdmap',
                username: self::env('MYSQL_USER', 'DB_USER') ?? 'root',
                password: self::env('MYSQL_PASSWORD', 'DB_PASSWORD') ?? '',
                port: (int) (self::env('MYSQL_PORT', 'DB_PORT') ?? 3306),
                sslMode: self::env('MYSQL_SSL_MODE') ?? 'REQUIRED',
                sslCa: self::env('MYSQL_SSL_CA'),
            );
        }

        $dbPath = self::env('DB_PATH') ?? 'database/crowdmap.sqlite';
        if ($dbPath !== ':memory:' && $dbPath[0] !== '/') {
            $dbPath = dirname(__DIR__, 2) . '/' . $dbPath;
        }

        return new SqliteConnection($dbPath);
    }

    private static function env(string ...$names): ?string
    {
        foreach ($names as $name) {
            $value = getenv($name);
            if ($value !== false && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
