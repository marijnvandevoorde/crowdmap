<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;

/**
 * Command to log out the current user session.
 */
final readonly class LogoutCommand implements Command
{
    public function __construct() {}
}
