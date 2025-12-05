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

    public function findByParentId(int $parentId): array
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE parent_id = :parent_id ORDER BY created_at',
            ['parent_id' => $parentId]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByParentIdAndContent(int $parentId, string $content): ?Statement
    {
        $rows = $this->connection->query(
            'SELECT * FROM statements WHERE parent_id = :parent_id AND content = :content',
            ['parent_id' => $parentId, 'content' => $content]
        );

        return empty($rows) ? null : $this->hydrate($rows[0]);
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

    public function merge(array $statementIds): Statement
    {
        if (count($statementIds) < 2) {
            throw new \InvalidArgumentException('At least 2 statements required for merge');
        }

        // Get all statements and verify they have the same parent
        $statements = [];
        $parentId = null;
        foreach ($statementIds as $id) {
            $statement = $this->findById($id);
            if ($statement === null) {
                throw new \InvalidArgumentException("Statement $id not found");
            }
            if ($parentId === null) {
                $parentId = $statement->parentId;
            } elseif ($statement->parentId !== $parentId) {
                throw new \InvalidArgumentException('All statements must have the same parent');
            }
            $statements[] = $statement;
        }

        // First statement becomes the target
        $targetId = $statementIds[0];
        $otherIds = array_slice($statementIds, 1);

        // Merge content from all statements as bullet points
        $mergedContent = implode("\n", array_map(fn($s) => '• ' . $s->content, $statements));
        $this->updateContent($targetId, $mergedContent);

        // Transfer children from other statements to target
        foreach ($otherIds as $otherId) {
            $this->connection->execute(
                'UPDATE statements SET parent_id = :target_id WHERE parent_id = :other_id',
                ['target_id' => $targetId, 'other_id' => $otherId]
            );
        }

        // Transfer votes from other statements to target (avoiding duplicates)
        foreach ($otherIds as $otherId) {
            // Get votes from other statement that don't already exist for target
            $this->connection->execute(
                'INSERT OR IGNORE INTO votes (statement_id, session_id, created_at)
                 SELECT :target_id, session_id, created_at FROM votes WHERE statement_id = :other_id',
                ['target_id' => $targetId, 'other_id' => $otherId]
            );
        }

        // Delete other statements (votes will cascade or be handled)
        foreach ($otherIds as $otherId) {
            $this->delete($otherId);
        }

        return $this->findById($targetId);
    }

    public function delete(int $id): void
    {
        // Delete votes first
        $this->connection->execute('DELETE FROM votes WHERE statement_id = :id', ['id' => $id]);
        // Delete statement
        $this->connection->execute('DELETE FROM statements WHERE id = :id', ['id' => $id]);
    }

    public function deleteWithDescendants(array $statementIds): void
    {
        // Collect all IDs to delete (including descendants)
        $allIdsToDelete = [];

        foreach ($statementIds as $id) {
            $this->collectDescendantIds((int) $id, $allIdsToDelete);
        }

        // Delete in reverse order (children first) to avoid foreign key issues
        $allIdsToDelete = array_reverse($allIdsToDelete);

        foreach ($allIdsToDelete as $id) {
            $this->delete($id);
        }
    }

    private function collectDescendantIds(int $id, array &$collected): void
    {
        if (in_array($id, $collected, true)) {
            return;
        }

        // First collect all children recursively
        $children = $this->findByParentId($id);
        foreach ($children as $child) {
            $this->collectDescendantIds($child->id, $collected);
        }

        // Then add this node
        $collected[] = $id;
    }

    public function updateContent(int $id, string $content): void
    {
        $this->connection->execute(
            'UPDATE statements SET content = :content WHERE id = :id',
            ['id' => $id, 'content' => $content]
        );
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
