<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandBus;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Simple command bus implementation that resolves handlers from a PSR-11 container.
 * Handler classes are resolved by convention: {CommandName} -> {CommandName}Handler
 */
final class SimpleCommandBus implements CommandBus
{
    /** @var array<class-string<Command>, class-string<CommandHandler>> */
    private array $handlerMap = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * Register a handler for a command.
     *
     * @param class-string<Command> $commandClass
     * @param class-string<CommandHandler> $handlerClass
     */
    public function register(string $commandClass, string $handlerClass): void
    {
        $this->handlerMap[$commandClass] = $handlerClass;
    }

    public function dispatch(Command $command): mixed
    {
        $commandClass = $command::class;

        if (!isset($this->handlerMap[$commandClass])) {
            throw new RuntimeException("No handler registered for command: {$commandClass}");
        }

        $handlerClass = $this->handlerMap[$commandClass];
        $handler = $this->container->get($handlerClass);

        if (!$handler instanceof CommandHandler) {
            throw new RuntimeException("Handler {$handlerClass} must implement CommandHandler interface");
        }

        return $handler->handle($command);
    }
}
