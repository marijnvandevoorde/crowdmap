<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when a user is not authorized to perform an action.
 */
final class UnauthorizedException extends DomainException
{
    public static function notOwner(): self
    {
        return new self('User is not the owner of this resource');
    }
}
