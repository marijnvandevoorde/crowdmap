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
    public function findByMindmapId(int $mindmapId): array;

    /** @return Statement[] */
    public function findByParentId(int $parentId): array;

    /** @return array{statement: Statement, vote_count: int}[] */
    public function findByMindmapIdWithVoteCounts(int $mindmapId): array;
}
