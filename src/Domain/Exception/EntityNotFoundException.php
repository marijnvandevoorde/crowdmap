<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when a requested entity cannot be found.
 */
final class EntityNotFoundException extends DomainException
{
    public static function mindmapNotFound(string $uuid): self
    {
        return new self("Mindmap not found: {$uuid}");
    }

    public static function statementNotFound(int $id): self
    {
        return new self("Statement not found: {$id}");
    }

    public static function userNotFound(int $id): self
    {
        return new self("User not found: {$id}");
    }
}
