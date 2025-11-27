<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Entity\Mindmap;

interface MindmapRepository
{
    public function save(Mindmap $mindmap): Mindmap;

    public function findById(int $id): ?Mindmap;

    public function findByUuid(string $uuid): ?Mindmap;

    /** @return Mindmap[] */
    public function findByUserId(int $userId): array;

    public function updateFocusedStatement(int $mindmapId, ?int $statementId): void;
}
