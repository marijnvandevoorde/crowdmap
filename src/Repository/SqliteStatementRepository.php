<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Entity\Statement;
use DateTimeImmutable;

class SqliteStatementRepository implements StatementRepository
{
    public function __construct(
        private readonly DatabaseConnection $connection,
    ) {}

    public function save(Statement $statement): Statement
    {
        $this->connection->execute(
            'INSERT INTO statements (mindmap_id, parent_id, content, session_id, created_at)
             VALUES (:mindmap_id, :parent_id, :content, :session_id, :created_at)',
            [
                'mindmap_id' => $statement->mindmapId,
                'parent_id' => $statement->parentId,
                'content' => $statement->content,
                'session_id' => $statement->sessionId,
                'created_at' => $statement->createdAt->format('Y-m-d H:i:s'),
            ]
        );

        return $statement->withId((int) $this->connection->lastInsertId());
    }

    public function findById(int $id): ?Statement
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE id = :id',
            ['id' => $id]
        );

        return empty($rows) ? null : $this->hydrate($rows[0]);
    }

    public function findRootByMindmapId(int $mindmapId): ?Statement
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE mindmap_id = :mindmap_id AND parent_id IS NULL',
            ['mindmap_id' => $mindmapId]
        );

        return empty($rows) ? null : $this->hydrate($rows[0]);
    }

    public function findByMindmapId(int $mindmapId): array
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE mindmap_id = :mindmap_id ORDER BY created_at',
            ['mindmap_id' => $mindmapId]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByParentId(int $parentId): array
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE parent_id = :parent_id ORDER BY created_at',
            ['parent_id' => $parentId]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByMindmapIdWithVoteCounts(int $mindmapId): array
    {
        $rows = $this->connection->query(
            'SELECT s.*, COUNT(v.id) as vote_count
             FROM statements s
             LEFT JOIN votes v ON v.statement_id = s.id
             WHERE s.mindmap_id = :mindmap_id
             GROUP BY s.id
             ORDER BY s.created_at',
            ['mindmap_id' => $mindmapId]
        );

        return array_map(function ($row) {
            return [
                'statement' => $this->hydrate($row),
                'vote_count' => (int) $row['vote_count'],
            ];
        }, $rows);
    }

    private function hydrate(array $row): Statement
    {
        return new Statement(
            id: (int) $row['id'],
            mindmapId: (int) $row['mindmap_id'],
            parentId: $row['parent_id'] ? (int) $row['parent_id'] : null,
            content: $row['content'],
            sessionId: $row['session_id'],
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
