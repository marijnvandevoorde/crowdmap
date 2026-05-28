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
                CREATE TABLE votes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    statement_id INT NOT NULL,
                    session_id VARCHAR(64) NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (statement_id) REFERENCES statements(id) ON DELETE CASCADE,
                    UNIQUE(statement_id, session_id)
                )
            ');
        } else {
            $connection->execute('
                CREATE TABLE votes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    statement_id INTEGER NOT NULL,
                    session_id VARCHAR(64) NOT NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (statement_id) REFERENCES statements(id) ON DELETE CASCADE,
                    UNIQUE(statement_id, session_id)
                )
            ');
        }

        $connection->execute('CREATE INDEX idx_votes_statement_id ON votes(statement_id)');
    }

    public function down(DatabaseConnection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS votes');
    }
};
