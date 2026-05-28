<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Auth;

use Crowdmap\Application\Command\Auth\RegisterCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class RegisterCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new RegisterCommand('test@example.com', 'Test User', 'password123');

        $this->assertSame('test@example.com', $command->email);
        $this->assertSame('Test User', $command->name);
        $this->assertSame('password123', $command->password);
    }

    public function testTrimsEmailAndName(): void
    {
        $command = new RegisterCommand('  test@example.com  ', '  Test User  ', 'password123');

        $this->assertSame('test@example.com', $command->email);
        $this->assertSame('Test User', $command->name);
    }

    public function testThrowsWhenEmailIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        new RegisterCommand('', 'Test User', 'password123');
    }

    public function testThrowsWhenNameIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        new RegisterCommand('test@example.com', '   ', 'password123');
    }

    public function testThrowsWhenPasswordIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        new RegisterCommand('test@example.com', 'Test User', '');
    }

    public function testThrowsForInvalidEmailFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');
        new RegisterCommand('not-an-email', 'Test User', 'password123');
    }

    public function testThrowsWhenPasswordTooShort(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least 8 characters');
        new RegisterCommand('test@example.com', 'Test User', 'short');
    }

    public function testAcceptsExactlyMinimumPasswordLength(): void
    {
        $command = new RegisterCommand('test@example.com', 'Test User', '12345678');

        $this->assertSame('12345678', $command->password);
    }

    public function testDoesNotTrimPassword(): void
    {
        $command = new RegisterCommand('test@example.com', 'Test User', '  spaced  ');

        $this->assertSame('  spaced  ', $command->password);
    }
}
