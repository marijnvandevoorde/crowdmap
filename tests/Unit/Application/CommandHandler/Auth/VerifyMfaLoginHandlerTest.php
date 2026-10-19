<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\VerifyMfaLoginCommand;
use Crowdmap\Application\CommandHandler\Auth\VerifyMfaLoginHandler;
use Crowdmap\Domain\Exception\InvalidMfaCodeException;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;

class VerifyMfaLoginHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private TotpService $totpService;
    private VerifyMfaLoginHandler $handler;
    private string $secret;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->totpService = new TotpService();
        $this->handler = new VerifyMfaLoginHandler($this->userRepository, $this->totpService);
        $this->secret = $this->totpService->generateSecret();
    }

    private function enrolledUser(array $recoveryPlainCodes = []): User
    {
        $hashes = array_map(
            static fn (string $code): string => User::hashRecoveryCode($code),
            $recoveryPlainCodes,
        );

        return User::create('a@b.com', 'Alice', 'password123')
            ->withId(1)
            ->withTotpSecret($this->secret)
            ->withMfaEnabled(true)
            ->withRecoveryCodes($hashes);
    }

    public function testAcceptsValidTotpCode(): void
    {
        $this->userRepository->expects($this->once())->method('findById')->with(1)->willReturn($this->enrolledUser());
        $this->userRepository->expects($this->never())->method('save');

        $code = TOTP::createFromSecret($this->secret)->now();
        $response = $this->handler->handle(new VerifyMfaLoginCommand(1, $code));

        $this->assertSame(1, $response->user->id);
    }

    public function testAcceptsRecoveryCodeAndConsumesIt(): void
    {
        $user = $this->enrolledUser(['abcde-fghij', 'klmno-pqrst']);
        $this->userRepository->expects($this->once())->method('findById')->with(1)->willReturn($user);

        $saved = null;
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $u) use (&$saved): User {
                $saved = $u;
                return $u;
            });

        $response = $this->handler->handle(new VerifyMfaLoginCommand(1, 'abcde-fghij'));

        $this->assertSame(1, $response->user->id);
        $this->assertCount(1, $saved->recoveryCodes);
        $this->assertNotContains(User::hashRecoveryCode('abcde-fghij'), $saved->recoveryCodes);
        $this->assertContains(User::hashRecoveryCode('klmno-pqrst'), $saved->recoveryCodes);
    }

    public function testRejectsInvalidCode(): void
    {
        $this->userRepository->expects($this->once())->method('findById')->with(1)->willReturn($this->enrolledUser(['abcde-fghij']));
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(InvalidMfaCodeException::class);
        $this->handler->handle(new VerifyMfaLoginCommand(1, 'wrong-code'));
    }

    public function testRejectsWhenMfaNotEnabled(): void
    {
        $user = User::create('a@b.com', 'Alice', 'password123')->withId(1);
        $this->userRepository->expects($this->once())->method('findById')->with(1)->willReturn($user);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(InvalidMfaCodeException::class);
        $this->handler->handle(new VerifyMfaLoginCommand(1, '123456'));
    }
}
