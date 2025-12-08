<?php

declare(strict_types=1);

namespace Crowdmap\Database;

use PDO;

class MysqlConnection implements DatabaseConnection
{
    private PDO $pdo;

    public function __construct(
        string $host,
        string $database,
        string $username,
        string $password,
        int $port = 3306
    ) {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
