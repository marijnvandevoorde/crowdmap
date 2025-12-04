<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\GetCurrentUserCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the GetCurrentUserCommand.
 *
 * @implements CommandHandler<GetCurrentUserCommand>
 */
final class GetCurrentUserHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param GetCurrentUserCommand $command
     * @return User The current user
     */
    public function handle(Command $command): User
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        return $user;
    }
}
