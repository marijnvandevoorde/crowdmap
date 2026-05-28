<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to register a new user with email, name and password.
 *
 * This command is self-validating - it guarantees a trimmed, valid email,
 * a non-empty name, and a password of at least the minimum length.
 */
final readonly class RegisterCommand implements Command
{
    public const MIN_PASSWORD_LENGTH = 8;

    public readonly string $email;
    public readonly string $name;
    public readonly string $password;

    public function __construct(string $email, string $name, string $password)
    {
        $trimmedEmail = trim($email);
        $trimmedName = trim($name);

        if ($trimmedEmail === '' || $trimmedName === '' || $password === '') {
            throw ValidationException::invalidInput('Email, name and password are required');
        }

        if (!filter_var($trimmedEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidInput('Invalid email format');
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw ValidationException::invalidInput(
                sprintf('Password must be at least %d characters', self::MIN_PASSWORD_LENGTH)
            );
        }

        $this->email = $trimmedEmail;
        $this->name = $trimmedName;
        $this->password = $password;
    }
}
