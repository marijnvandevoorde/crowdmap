<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Exception thrown when login credentials are invalid.
 */
final class InvalidCredentialsException extends DomainException
{
    public static function create(): self
    {
        return new self('Invalid credentials');
    }
}
