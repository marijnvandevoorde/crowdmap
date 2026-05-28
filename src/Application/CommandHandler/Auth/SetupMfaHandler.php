<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\SetupMfaCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\SetupMfaResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the SetupMfaCommand: generates a fresh TOTP secret and stores it
 * provisionally (MFA stays disabled until confirmed).
 *
 * @implements CommandHandler<SetupMfaCommand, SetupMfaResponse>
 */
final class SetupMfaHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpService $totpService,
    ) {}

    /**
     * @param SetupMfaCommand $command
     */
    public function handle(Command $command): SetupMfaResponse
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        $secret = $this->totpService->generateSecret();

        $user = $user
            ->withTotpSecret($secret)
            ->withMfaEnabled(false)
            ->withRecoveryCodes(null);
        $this->userRepository->save($user);

        return new SetupMfaResponse(
            secret: $secret,
            otpauthUri: $this->totpService->provisioningUri($secret, $user->email),
        );
    }
}
