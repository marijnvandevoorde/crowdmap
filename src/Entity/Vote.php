<?php

declare(strict_types=1);

namespace Crowdmap\Entity;

use DateTimeImmutable;

class Vote
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $statementId,
        public readonly string $sessionId,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(int $statementId, string $sessionId): self
    {
        return new self(
            id: null,
            statementId: $statementId,
            sessionId: $sessionId,
            createdAt: new DateTimeImmutable(),
        );
    }
}
