<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\ConfirmMfaCommand;
use Crowdmap\Application\CommandHandler\Auth\ConfirmMfaHandler;
use Crowdmap\Domain\Exception\InvalidMfaCodeException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;

class ConfirmMfaHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private TotpService $totpService;
    private ConfirmMfaHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->totpService = new TotpService();
        $this->handler = new ConfirmMfaHandler($this->userRepository, $this->totpService);
    }

    public function testEnablesMfaAndReturnsRecoveryCodesOnValidCode(): void
    {
        $secret = $this->totpService->generateSecret();
        $user = User::create('a@b.com', 'Alice', 'password123')
            ->withId(1)
            ->withTotpSecret($secret);

        $this->userRepository->method('findById')->with(1)->willReturn($user);

        $saved = null;
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$saved): User {
                $saved = $u;
                return $u;
            });

        $validCode = TOTP::createFromSecret($secret)->now();
        $response = $this->handler->handle(new ConfirmMfaCommand(1, $validCode));

        $this->assertCount(8, $response->recoveryCodes);
        $this->assertTrue($saved->mfaEnabled);
        $this->assertCount(8, $saved->recoveryCodes);
        // stored codes are hashes, not the plaintext shown to the user
        $this->assertNotContains($response->recoveryCodes[0], $saved->recoveryCodes);
        $this->assertContains(User::hashRecoveryCode($response->recoveryCodes[0]), $saved->recoveryCodes);
    }

    public function testThrowsOnInvalidCode(): void
    {
        $secret = $this->totpService->generateSecret();
        $user = User::create('a@b.com', 'Alice', 'password123')
            ->withId(1)
            ->withTotpSecret($secret);

        $this->userRepository->method('findById')->with(1)->willReturn($user);
        $this->userRepository->expects($this->never())->method('save');

        // Pick a code guaranteed to differ from the current valid one.
        $valid = TOTP::createFromSecret($secret)->now();
        $wrong = $valid === '000000' ? '111111' : '000000';

        $this->expectException(InvalidMfaCodeException::class);
        $this->handler->handle(new ConfirmMfaCommand(1, $wrong));
    }

    public function testThrowsWhenSetupNotStarted(): void
    {
        $user = User::create('a@b.com', 'Alice', 'password123')->withId(1);

        $this->userRepository->expects($this->once())->method('findById')->with(1)->willReturn($user);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);
        $this->handler->handle(new ConfirmMfaCommand(1, '123456'));
    }
}
