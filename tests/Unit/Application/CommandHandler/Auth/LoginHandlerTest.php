<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\CommandHandler\Auth\LoginHandler;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;
use DateTimeImmutable;
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

    public function testSuccessfulLogin(): void
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

    public function testLoginTrimsEmail(): void
    {
        $email = '  test@example.com  ';
        $trimmedEmail = 'test@example.com';
        $password = 'correct-password';

        $user = User::create($trimmedEmail, 'Test User', $password);
        $userWithId = $user->withId(1);

        $command = new LoginCommand($email, $password);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($trimmedEmail)
            ->willReturn($userWithId);

        $response = $this->handler->handle($command);

        $this->assertSame($userWithId, $response->user);
    }

    public function testThrowsValidationExceptionWhenEmailIsEmpty(): void
    {
        $command = new LoginCommand('', 'password');

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenEmailIsWhitespace(): void
    {
        $command = new LoginCommand('   ', 'password');

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenPasswordIsEmpty(): void
    {
        $command = new LoginCommand('test@example.com', '');

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenBothAreEmpty(): void
    {
        $command = new LoginCommand('', '');

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        $this->handler->handle($command);
    }

    public function testThrowsInvalidCredentialsExceptionWhenUserNotFound(): void
    {
        $command = new LoginCommand('nonexistent@example.com', 'password');

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

    public function testDoesNotRevealWhetherUserExistsOrPasswordIsWrong(): void
    {
        // This test verifies that both "user not found" and "wrong password"
        // throw the same exception type, preventing username enumeration

        $email = 'test@example.com';
        $password = 'password';

        // Test 1: User not found
        $command1 = new LoginCommand($email, $password);
        $this->userRepository->method('findByEmail')->willReturn(null);

        $exception1 = null;
        try {
            $this->handler->handle($command1);
        } catch (InvalidCredentialsException $e) {
            $exception1 = $e;
        }

        // Test 2: Wrong password
        $user = User::create($email, 'Test User', 'different-password');
        $userWithId = $user->withId(1);

        $command2 = new LoginCommand($email, $password);
        $this->userRepository->method('findByEmail')->willReturn($userWithId);

        $exception2 = null;
        try {
            $this->handler->handle($command2);
        } catch (InvalidCredentialsException $e) {
            $exception2 = $e;
        }

        // Both should throw InvalidCredentialsException
        $this->assertInstanceOf(InvalidCredentialsException::class, $exception1);
        $this->assertInstanceOf(InvalidCredentialsException::class, $exception2);

        // Both should have the same message (not revealing which case it is)
        $this->assertSame($exception1->getMessage(), $exception2->getMessage());
    }
}
