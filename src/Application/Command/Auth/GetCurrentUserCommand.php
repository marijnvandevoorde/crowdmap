<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;

/**
 * Command to retrieve the currently authenticated user.
 */
final readonly class GetCurrentUserCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}
}
