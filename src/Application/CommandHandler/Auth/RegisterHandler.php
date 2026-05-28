<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\RegisterCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\RegisterResponse;
use Crowdmap\Domain\Exception\EmailAlreadyExistsException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the RegisterCommand.
 *
 * @implements CommandHandler<RegisterCommand, RegisterResponse>
 */
final class RegisterHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param RegisterCommand $command
     */
    public function handle(Command $command): RegisterResponse
    {
        if ($this->userRepository->findByEmail($command->email) !== null) {
            throw EmailAlreadyExistsException::create();
        }

        $user = User::create($command->email, $command->name, $command->password);
        $user = $this->userRepository->save($user);

        return new RegisterResponse($user);
    }
}
