<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LogoutCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\CommandResponse;

/**
 * Handles the LogoutCommand.
 *
 * @implements CommandHandler<LogoutCommand, null>
 */
final class LogoutHandler implements CommandHandler
{
    /**
     * @param LogoutCommand $command
     */
    public function handle(Command $command): ?CommandResponse
    {
        session_start();
        session_destroy();

        return null;
    }
}
