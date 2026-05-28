<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Thrown when a submitted MFA (TOTP or recovery) code is not valid.
 */
final class InvalidMfaCodeException extends DomainException
{
    public static function create(): self
    {
        return new self('Invalid verification code');
    }
}
