<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Integration;

use Crowdmap\Database\ConnectionFactory;
use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\Migrator;
use Crowdmap\Database\SqliteConnection;
use PHPUnit\Framework\TestCase;

/**
 * Runs against in-memory SQLite by default. With TEST_DB=mysql it runs against the
 * MySQL database configured by MYSQL_* instead; every table in it is dropped first.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected DatabaseConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::usesMysql()) {
            $this->connection = ConnectionFactory::fromEnvironment();
            $this->dropAllTables();
        } else {
            $this->connection = new SqliteConnection(':memory:');
        }

        $migrator = new Migrator(
            $this->connection,
            __DIR__ . '/../../database/migrations'
        );
        $migrator->migrate();
    }

    protected static function usesMysql(): bool
    {
        return getenv('TEST_DB') === 'mysql';
    }

    private function dropAllTables(): void
    {
        $pdo = $this->connection->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $pdo->exec("DROP TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
