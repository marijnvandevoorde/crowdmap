<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when attempting to merge statements that don't share the same parent.
 */
final class StatementsNotSiblingsException extends DomainException
{
    public static function create(): self
    {
        return new self('All statements must have the same parent to be merged');
    }
}
