<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Integration;

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\Migrator;
use Crowdmap\Database\SqliteConnection;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected DatabaseConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory SQLite database
        $this->connection = new SqliteConnection(':memory:');

        // Run migrations
        $migrator = new Migrator(
            $this->connection,
            __DIR__ . '/../../database/migrations'
        );
        $migrator->migrate();
    }

    protected function tearDown(): void
    {
        // Connection will be automatically closed when test ends
        parent::tearDown();
    }
}
