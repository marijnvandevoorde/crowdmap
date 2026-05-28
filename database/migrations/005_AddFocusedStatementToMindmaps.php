<?php

declare(strict_types=1);

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\Migration;

return new class implements Migration {
    public function up(DatabaseConnection $connection): void
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $connection->execute('
                ALTER TABLE mindmaps ADD COLUMN focused_statement_id INT DEFAULT NULL
            ');
        } else {
            $connection->execute('
                ALTER TABLE mindmaps ADD COLUMN focused_statement_id INTEGER DEFAULT NULL
            ');
        }
    }

    public function down(DatabaseConnection $connection): void
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $connection->execute('ALTER TABLE mindmaps DROP COLUMN focused_statement_id');
        }
        // SQLite doesn't support DROP COLUMN easily, so we skip it for SQLite
    }
};
