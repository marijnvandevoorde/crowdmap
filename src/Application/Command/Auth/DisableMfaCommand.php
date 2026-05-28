<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to disable MFA for a user, re-authenticated with their password.
 */
final readonly class DisableMfaCommand implements Command
{
    public readonly int $userId;
    public readonly string $password;

    public function __construct(int $userId, string $password)
    {
        if (trim($password) === '') {
            throw ValidationException::invalidInput('Your password is required');
        }

        $this->userId = $userId;
        $this->password = $password;
    }
}
