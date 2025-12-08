<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Entity\Mindmap;
use DateTimeImmutable;

class MysqlMindmapRepository implements MindmapRepository
{
    public function __construct(
        private readonly DatabaseConnection $connection,
    ) {}

    public function save(Mindmap $mindmap): Mindmap
    {
        $this->connection->execute(
            'INSERT INTO mindmaps (uuid, user_id, created_at) VALUES (:uuid, :user_id, :created_at)',
            [
                'uuid' => $mindmap->uuid,
                'user_id' => $mindmap->userId,
                'created_at' => $mindmap->createdAt->format('Y-m-d H:i:s'),
            ]
        );

        return $mindmap->withId((int) $this->connection->lastInsertId());
    }

    public function findById(int $id): ?Mindmap
    {
        $rows = $this->connection->query(
            'SELECT * FROM mindmaps WHERE id = :id',
            ['id' => $id]
        );

        return empty($rows) ? null : $this->hydrate($rows[0]);
    }

    public function findByUuid(string $uuid): ?Mindmap
    {
        $rows = $this->connection->query(
            'SELECT * FROM mindmaps WHERE uuid = :uuid',
            ['uuid' => $uuid]
        );

        return empty($rows) ? null : $this->hydrate($rows[0]);
    }

    public function findByUserId(int $userId): array
    {
        $rows = $this->connection->query(
            'SELECT * FROM mindmaps WHERE user_id = :user_id ORDER BY created_at DESC',
            ['user_id' => $userId]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    public function updateFocusedStatement(int $mindmapId, ?int $statementId): void
    {
        $this->connection->execute(
            'UPDATE mindmaps SET focused_statement_id = :statement_id WHERE id = :id',
            [
                'statement_id' => $statementId,
                'id' => $mindmapId,
            ]
        );
    }

    private function hydrate(array $row): Mindmap
    {
        return new Mindmap(
            id: (int) $row['id'],
            uuid: $row['uuid'],
            userId: (int) $row['user_id'],
            focusedStatementId: isset($row['focused_statement_id']) ? (int) $row['focused_statement_id'] : null,
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
