<?php

declare(strict_types=1);

namespace Crowdmap\Database;

class Migrator
{
    public function __construct(
        private readonly DatabaseConnection $connection,
        private readonly string $migrationsPath,
    ) {}

    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        $newMigrations = [];

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if (in_array($migrationName, $applied, true)) {
                continue;
            }

            $migration = require $file;
            $migration->up($this->connection);

            $this->connection->execute(
                'INSERT INTO migrations (migration) VALUES (:migration)',
                ['migration' => $migrationName]
            );

            $newMigrations[] = $migrationName;
        }

        return $newMigrations;
    }

    public function rollback(): ?string
    {
        $this->ensureMigrationsTable();

        $rows = $this->connection->query(
            'SELECT migration FROM migrations ORDER BY id DESC LIMIT 1'
        );

        if (empty($rows)) {
            return null;
        }

        $migrationName = $rows[0]['migration'];
        $file = $this->migrationsPath . '/' . $migrationName . '.php';

        if (file_exists($file)) {
            $migration = require $file;
            $migration->down($this->connection);
        }

        $this->connection->execute(
            'DELETE FROM migrations WHERE migration = :migration',
            ['migration' => $migrationName]
        );

        return $migrationName;
    }

    private function ensureMigrationsTable(): void
    {
        $this->connection->execute('
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function getAppliedMigrations(): array
    {
        $rows = $this->connection->query('SELECT migration FROM migrations');
        return array_column($rows, 'migration');
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        return $files;
    }
}
