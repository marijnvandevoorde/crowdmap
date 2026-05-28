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
                CREATE TABLE mindmaps (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    user_id INT NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');
        } else {
            $connection->execute('
                CREATE TABLE mindmaps (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    user_id INTEGER NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ');
        }

        $connection->execute('CREATE INDEX idx_mindmaps_uuid ON mindmaps(uuid)');
        $connection->execute('CREATE INDEX idx_mindmaps_user_id ON mindmaps(user_id)');
    }

    public function down(DatabaseConnection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS mindmaps');
    }
};
