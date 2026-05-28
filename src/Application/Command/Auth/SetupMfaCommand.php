<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;

/**
 * Command to begin MFA enrollment for a user (generates a TOTP secret).
 */
final readonly class SetupMfaCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}
}
