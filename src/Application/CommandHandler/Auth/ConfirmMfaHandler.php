<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\ConfirmMfaCommand;
use Crowdmap\Application\Command\Command;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Auth\ConfirmMfaResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidMfaCodeException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;

/**
 * Handles the ConfirmMfaCommand: verifies the first TOTP code, enables MFA,
 * and issues one-time recovery codes.
 *
 * @implements CommandHandler<ConfirmMfaCommand, ConfirmMfaResponse>
 */
final class ConfirmMfaHandler implements CommandHandler
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TotpService $totpService,
    ) {}

    /**
     * @param ConfirmMfaCommand $command
     */
    public function handle(Command $command): ConfirmMfaResponse
    {
        $user = $this->userRepository->findById($command->userId);

        if ($user === null) {
            throw EntityNotFoundException::userNotFound($command->userId);
        }

        if ($user->totpSecret === null) {
            throw ValidationException::invalidInput('MFA setup has not been started');
        }

        if (!$this->totpService->verify($user->totpSecret, $command->code)) {
            throw InvalidMfaCodeException::create();
        }

        $plainCodes = [];
        $hashedCodes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $code = self::generateRecoveryCode();
            $plainCodes[] = $code;
            $hashedCodes[] = User::hashRecoveryCode($code);
        }

        $user = $user
            ->withMfaEnabled(true)
            ->withRecoveryCodes($hashedCodes);
        $this->userRepository->save($user);

        return new ConfirmMfaResponse($plainCodes);
    }

    private static function generateRecoveryCode(): string
    {
        $raw = bin2hex(random_bytes(5)); // 10 hex chars
        return substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
    }
}
