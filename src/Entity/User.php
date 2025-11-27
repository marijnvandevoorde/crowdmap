<?php

declare(strict_types=1);

namespace Crowdmap\Entity;

use DateTimeImmutable;

class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $passwordHash,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(string $email, string $name, string $password): self
    {
        return new self(
            id: null,
            email: $email,
            name: $name,
            passwordHash: password_hash($password, PASSWORD_DEFAULT),
            createdAt: new DateTimeImmutable(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            email: $this->email,
            name: $this->name,
            passwordHash: $this->passwordHash,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
