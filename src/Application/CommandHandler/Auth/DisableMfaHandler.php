<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\DisableMfaCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the DisableMfaCommand: clears the TOTP secret and recovery codes
 * after re-verifying the user's password.
 *
 * @implements CommandHandler<DisableMfaCommand, null>
 */
final class DisableMfaHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @param DisableMfaCommand $command
     */
    public function handle(Command $command): ?CommandResponse
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        if (!$user->verifyPassword($command->password)) {
            throw InvalidCredentialsException::create();
        }

        $user = $user
            ->withMfaEnabled(false)
            ->withTotpSecret(null)
            ->withRecoveryCodes(null);
        $this->userRepository->save($user);

        return null;
    }
}
