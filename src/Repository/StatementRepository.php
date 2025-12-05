<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Entity\Statement;

interface StatementRepository
{
    public function save(Statement $statement): Statement;

    public function findById(int $id): ?Statement;

    public function findRootByMindmapId(int $mindmapId): ?Statement;

    /** @return Statement[] */
    public function findByParentId(int $parentId): array;

    public function findByParentIdAndContent(int $parentId, string $content): ?Statement;

    /** @return array{statement: Statement, vote_count: int}[] */
    public function findByMindmapIdWithVoteCounts(int $mindmapId): array;

    /**
     * Merge multiple statements into one. The first statement becomes the merged statement,
     * others are deleted, and their children/votes are transferred.
     * @param int[] $statementIds
     */
    public function merge(array $statementIds): Statement;

    public function delete(int $id): void;

    /**
     * Delete multiple statements and all their descendants.
     * @param int[] $statementIds
     */
    public function deleteWithDescendants(array $statementIds): void;

    public function updateContent(int $id, string $content): void;
}
