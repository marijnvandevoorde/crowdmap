<?php

declare(strict_types=1);

namespace Crowdmap\Entity;

use DateTimeImmutable;

class User
{
    /**
     * @param string[]|null $recoveryCodes sha256 hashes of unused recovery codes
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $passwordHash,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt = null,
        public readonly bool $mfaEnabled = false,
        public readonly ?string $totpSecret = null,
        public readonly ?array $recoveryCodes = null,
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
            mfaEnabled: $this->mfaEnabled,
            totpSecret: $this->totpSecret,
            recoveryCodes: $this->recoveryCodes,
        );
    }

    public function withTotpSecret(?string $totpSecret): self
    {
        return new self(
            id: $this->id,
            email: $this->email,
            name: $this->name,
            passwordHash: $this->passwordHash,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            mfaEnabled: $this->mfaEnabled,
            totpSecret: $totpSecret,
            recoveryCodes: $this->recoveryCodes,
        );
    }

    public function withMfaEnabled(bool $mfaEnabled): self
    {
        return new self(
            id: $this->id,
            email: $this->email,
            name: $this->name,
            passwordHash: $this->passwordHash,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            mfaEnabled: $mfaEnabled,
            totpSecret: $this->totpSecret,
            recoveryCodes: $this->recoveryCodes,
        );
    }

    /**
     * @param string[]|null $recoveryCodes
     */
    public function withRecoveryCodes(?array $recoveryCodes): self
    {
        return new self(
            id: $this->id,
            email: $this->email,
            name: $this->name,
            passwordHash: $this->passwordHash,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            mfaEnabled: $this->mfaEnabled,
            totpSecret: $this->totpSecret,
            recoveryCodes: $recoveryCodes,
        );
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function verifyRecoveryCode(string $code): bool
    {
        $hash = self::hashRecoveryCode($code);

        foreach ($this->recoveryCodes ?? [] as $stored) {
            if (hash_equals($stored, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a copy of the user with the matching recovery code removed.
     */
    public function consumeRecoveryCode(string $code): self
    {
        $hash = self::hashRecoveryCode($code);
        $remaining = array_values(array_filter(
            $this->recoveryCodes ?? [],
            static fn (string $stored): bool => !hash_equals($stored, $hash),
        ));

        return $this->withRecoveryCodes($remaining);
    }

    public static function hashRecoveryCode(string $code): string
    {
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code) ?? '');
        return hash('sha256', $normalized);
    }
}
