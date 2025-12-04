<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when validation fails.
 */
final class ValidationException extends DomainException
{
    public static function emptyContent(): self
    {
        return new self('Content cannot be empty');
    }

    public static function invalidInput(string $message): self
    {
        return new self($message);
    }
}
