<?php

declare(strict_types=1);

namespace Crowdmap\Entity;

use DateTimeImmutable;

class Statement
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $mindmapId,
        public readonly ?int $parentId,
        public readonly string $content,
        public readonly ?string $sessionId,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function createRoot(int $mindmapId, string $content): self
    {
        return new self(
            id: null,
            mindmapId: $mindmapId,
            parentId: null,
            content: $content,
            sessionId: null,
            createdAt: new DateTimeImmutable(),
        );
    }

    public static function createReaction(int $mindmapId, int $parentId, string $content, string $sessionId): self
    {
        return new self(
            id: null,
            mindmapId: $mindmapId,
            parentId: $parentId,
            content: $content,
            sessionId: $sessionId,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            mindmapId: $this->mindmapId,
            parentId: $this->parentId,
            content: $this->content,
            sessionId: $this->sessionId,
            createdAt: $this->createdAt,
        );
    }
}
