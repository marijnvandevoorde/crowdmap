<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\GetCurrentUserCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\GetCurrentUserResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the GetCurrentUserCommand.
 *
 * @implements CommandHandler<GetCurrentUserCommand, GetCurrentUserResponse>
 */
final class GetCurrentUserHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param GetCurrentUserCommand $command
     */
    public function handle(Command $command): GetCurrentUserResponse
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        return new GetCurrentUserResponse($user);
    }
}
