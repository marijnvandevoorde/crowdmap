<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command for the second stage of login: verify a TOTP or recovery code
 * for a user who has already passed the password check.
 */
final readonly class VerifyMfaLoginCommand implements Command
{
    public readonly int $userId;
    public readonly string $code;

    public function __construct(int $userId, string $code)
    {
        $trimmedCode = trim($code);

        if ($trimmedCode === '') {
            throw ValidationException::invalidInput('A verification code is required');
        }

        $this->userId = $userId;
        $this->code = $trimmedCode;
    }
}
