<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\LoginResponse;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Domain\Exception\ValidationException;
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
        $email = trim($command->email);
        $password = $command->password;

        if ($email === '' || $password === '') {
            throw ValidationException::invalidInput('Email and password are required');
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$user->verifyPassword($password)) {
            throw InvalidCredentialsException::create();
        }

        return new LoginResponse($user);
    }
}
