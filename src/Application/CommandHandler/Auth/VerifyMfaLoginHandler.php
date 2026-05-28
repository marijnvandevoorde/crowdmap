<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\VerifyMfaLoginCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\VerifyMfaLoginResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidMfaCodeException;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the VerifyMfaLoginCommand: second login stage. Accepts a valid TOTP
 * code, or a one-time recovery code (which is then consumed).
 *
 * @implements CommandHandler<VerifyMfaLoginCommand, VerifyMfaLoginResponse>
 */
final class VerifyMfaLoginHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpService $totpService,
    ) {}

    /**
     * @param VerifyMfaLoginCommand $command
     */
    public function handle(Command $command): VerifyMfaLoginResponse
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        if (!$user->mfaEnabled || $user->totpSecret === null) {
            throw InvalidMfaCodeException::create();
        }

        if ($this->totpService->verify($user->totpSecret, $command->code)) {
            return new VerifyMfaLoginResponse($user);
        }

        if ($user->verifyRecoveryCode($command->code)) {
            $user = $user->consumeRecoveryCode($command->code);
            $this->userRepository->save($user);

            return new VerifyMfaLoginResponse($user);
        }

        throw InvalidMfaCodeException::create();
    }
}
