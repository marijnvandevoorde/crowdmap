<?php

declare(strict_types=1);

namespace Crowdmap\Entity;

use DateTimeImmutable;

class Mindmap
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly ?int $focusedStatementId,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(int $userId): self
    {
        return new self(
            id: null,
            uuid: self::generateUuid(),
            userId: $userId,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            uuid: $this->uuid,
            userId: $this->userId,
            focusedStatementId: $this->focusedStatementId,
            createdAt: $this->createdAt,
        );
    }

    public function withFocusedStatementId(?int $focusedStatementId): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            userId: $this->userId,
            focusedStatementId: $focusedStatementId,
            createdAt: $this->createdAt,
        );
    }

    private static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
