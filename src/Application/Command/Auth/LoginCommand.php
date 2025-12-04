<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Auth;

use Crowdmap\Application\Command\Command;

/**
 * Command to authenticate a user with email and password.
 */
final readonly class LoginCommand implements Command
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
