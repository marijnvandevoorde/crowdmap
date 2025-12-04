<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when attempting to delete the root statement of a mindmap.
 */
final class CannotDeleteRootException extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot delete root statement');
    }
}
