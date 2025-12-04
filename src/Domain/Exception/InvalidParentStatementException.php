<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when a parent statement is invalid (doesn't exist or doesn't belong to the mindmap).
 */
final class InvalidParentStatementException extends DomainException
{
    public static function create(int $parentId): self
    {
        return new self("Invalid parent statement: {$parentId}");
    }
}
