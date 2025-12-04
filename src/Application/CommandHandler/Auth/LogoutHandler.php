<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LogoutCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;

/**
 * Handles the LogoutCommand.
 *
 * @implements CommandHandler<LogoutCommand>
 */
final class LogoutHandler implements CommandHandler
{
    /**
     * @param LogoutCommand $command
     */
    public function handle(Command $command): mixed
    {
        session_start();
        session_destroy();

        return null;
    }
}
