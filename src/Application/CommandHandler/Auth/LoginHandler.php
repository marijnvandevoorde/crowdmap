<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the LoginCommand.
 *
 * @implements CommandHandler<LoginCommand>
 */
final class LoginHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param LoginCommand $command
     * @return User The authenticated user
     */
    public function handle(Command $command): User
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

        return $user;
    }
}
