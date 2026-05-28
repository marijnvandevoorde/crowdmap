<?php

declare(strict_types=1);

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\Migration;

return new class implements Migration {
    public function up(DatabaseConnection $connection): void
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        // Add unique constraint on parent_id + content combination
        // This prevents duplicate statements with the same content under the same parent
        if ($driver === 'mysql') {
            $connection->execute('
                CREATE UNIQUE INDEX idx_statements_parent_content
                ON statements(parent_id, content(255))
            ');
        } else {
            $connection->execute('
                CREATE UNIQUE INDEX idx_statements_parent_content
                ON statements(parent_id, content)
            ');
        }
    }

    public function down(DatabaseConnection $connection): void
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $connection->execute('DROP INDEX idx_statements_parent_content ON statements');
        } else {
            $connection->execute('DROP INDEX IF EXISTS idx_statements_parent_content');
        }
    }
};
