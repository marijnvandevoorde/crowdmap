<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\CommandHandler\Auth\LoginHandler;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class LoginHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private LoginHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new LoginHandler($this->userRepository);
    }

    public function testSuccessfullyAuthenticatesUser(): void
    {
        $email = 'test@example.com';
        $password = 'correct-password';

        $user = User::create($email, 'Test User', $password);
        $userWithId = $user->withId(1);

        $command = new LoginCommand($email, $password);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userWithId);

        $response = $this->handler->handle($command);

        $this->assertSame($userWithId, $response->user);
        $this->assertSame(1, $response->user->id);
        $this->assertSame($email, $response->user->email);
        $this->assertSame('Test User', $response->user->name);
    }

    public function testThrowsInvalidCredentialsExceptionWhenUserNotFound(): void
    {
        $command = new LoginCommand('nonexistent@example.com', 'password123');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle($command);
    }

    public function testThrowsInvalidCredentialsExceptionWhenPasswordIsIncorrect(): void
    {
        $email = 'test@example.com';
        $correctPassword = 'correct-password';
        $wrongPassword = 'wrong-password';

        $user = User::create($email, 'Test User', $correctPassword);
        $userWithId = $user->withId(1);

        $command = new LoginCommand($email, $wrongPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userWithId);

        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle($command);
    }
}

