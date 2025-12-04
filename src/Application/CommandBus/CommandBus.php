<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandBus;

use Crowdmap\Application\Command\Command;

/**
 * Interface for the command bus.
 * The command bus is responsible for dispatching commands to their handlers.
 */
interface CommandBus
{
    /**
     * Dispatch a command to its handler.
     *
     * @param Command $command
     * @return mixed The result from the handler
     */
    public function dispatch(Command $command): mixed;
}
