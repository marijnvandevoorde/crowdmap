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
                CREATE TABLE statements (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    mindmap_id INT NOT NULL,
                    parent_id INT NULL,
                    content TEXT NOT NULL,
                    session_id VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (mindmap_id) REFERENCES mindmaps(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES statements(id) ON DELETE CASCADE
                )
            ');
        } else {
            $connection->execute('
                CREATE TABLE statements (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    mindmap_id INTEGER NOT NULL,
                    parent_id INTEGER NULL,
                    content TEXT NOT NULL,
                    session_id VARCHAR(64) NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (mindmap_id) REFERENCES mindmaps(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES statements(id) ON DELETE CASCADE
                )
            ');
        }

        $connection->execute('CREATE INDEX idx_statements_mindmap_id ON statements(mindmap_id)');
        $connection->execute('CREATE INDEX idx_statements_parent_id ON statements(parent_id)');
    }

    public function down(DatabaseConnection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS statements');
    }
};
