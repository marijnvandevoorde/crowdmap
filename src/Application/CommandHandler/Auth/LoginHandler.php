<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\LoginResponse;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the LoginCommand.
 *
 * @implements CommandHandler<LoginCommand, LoginResponse>
 */
final class LoginHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param LoginCommand $command
     */
    public function handle(Command $command): LoginResponse
    {
        // Command is self-validating, so we can trust it's valid
        $user = $this->userRepository->findByEmail($command->email);

        if ($user === null || !$user->verifyPassword($command->password)) {
            throw InvalidCredentialsException::create();
        }

        return new LoginResponse($user);
    }
}
