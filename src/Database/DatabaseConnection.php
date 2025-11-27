<?php

declare(strict_types=1);

namespace Crowdmap\Database;

use PDO;

interface DatabaseConnection
{
    public function getPdo(): PDO;

    public function execute(string $sql, array $params = []): bool;

    public function query(string $sql, array $params = []): array;

    public function lastInsertId(): string;
}
