<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Auth;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class LoginCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new LoginCommand('test@example.com', 'password123');

        $this->assertSame('test@example.com', $command->email);
        $this->assertSame('password123', $command->password);
    }

    public function testTrimsEmail(): void
    {
        $command = new LoginCommand('  test@example.com  ', 'password123');

        $this->assertSame('test@example.com', $command->email);
    }

    public function testTrimsPassword(): void
    {
        $command = new LoginCommand('test@example.com', '  password123  ');

        $this->assertSame('password123', $command->password);
    }

    public function testThrowsValidationExceptionWhenEmailIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        new LoginCommand('', 'password123');
    }

    public function testThrowsValidationExceptionWhenEmailIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        new LoginCommand('   ', 'password123');
    }

    public function testThrowsValidationExceptionWhenPasswordIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        new LoginCommand('test@example.com', '');
    }

    public function testThrowsValidationExceptionWhenPasswordIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        new LoginCommand('test@example.com', '   ');
    }

    public function testThrowsValidationExceptionWhenBothAreEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email and password are required');

        new LoginCommand('', '');
    }

    public function testThrowsValidationExceptionForInvalidEmailFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new LoginCommand('not-an-email', 'password123');
    }

    public function testThrowsValidationExceptionForEmailWithoutAtSign(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new LoginCommand('testexample.com', 'password123');
    }

    public function testThrowsValidationExceptionForEmailWithoutDomain(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new LoginCommand('test@', 'password123');
    }

    public function testAcceptsValidEmailFormats(): void
    {
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'test123@sub.domain.example.com',
        ];

        foreach ($validEmails as $email) {
            $command = new LoginCommand($email, 'password123');
            $this->assertSame($email, $command->email);
        }
    }
}
