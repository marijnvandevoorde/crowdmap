<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to authenticate a user with email and password.
 *
 * This command is self-validating - it guarantees that email and password
 * are non-empty and that the email is trimmed and valid.
 */
final readonly class LoginCommand implements Command
{
    public readonly string $email;
    public readonly string $password;

    public function __construct(string $email, string $password)
    {
        $trimmedEmail = trim($email);
        $trimmedPassword = trim($password);

        if ($trimmedEmail === '' || $trimmedPassword === '') {
            throw ValidationException::invalidInput('Email and password are required');
        }

        if (!filter_var($trimmedEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidInput('Invalid email format');
        }

        $this->email = $trimmedEmail;
        $this->password = $trimmedPassword;
    }
}
