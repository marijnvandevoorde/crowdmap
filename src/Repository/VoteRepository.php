<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Entity\Vote;

interface VoteRepository
{
    public function save(Vote $vote): Vote;

    public function delete(int $statementId, string $sessionId): bool;

    public function findByStatementAndSession(int $statementId, string $sessionId): ?Vote;

    public function countByStatementId(int $statementId): int;
}
