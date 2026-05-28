<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to confirm and enable MFA by verifying a TOTP code.
 */
final readonly class ConfirmMfaCommand implements Command
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
