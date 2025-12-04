<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandResponse\CommandResponse;

/**
 * Interface for command handlers.
 * Each handler is responsible for executing a specific command.
 *
 * @template TCommand of Command
 * @template TResponse of CommandResponse|null
 */
interface CommandHandler
{
    /**
     * Handle the command and return the result.
     *
     * @param TCommand $command
     * @return TResponse
     */
    public function handle(Command $command): ?CommandResponse;
}
