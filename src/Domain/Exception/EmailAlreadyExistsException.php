<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when registering with an email that is already taken.
 */
final class EmailAlreadyExistsException extends DomainException
{
    public static function create(): self
    {
        return new self('An account with this email already exists');
    }
}
