<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler;

use Crowdmap\Application\Command\Command;

/**
 * Interface for command handlers.
 * Each handler is responsible for executing a specific command.
 *
 * @template T of Command
 */
interface CommandHandler
{
    /**
     * Handle the command and return the result.
     *
     * @param T $command
     * @return mixed
     */
    public function handle(Command $command): mixed;
}
