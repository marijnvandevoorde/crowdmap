<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Auth;

use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Entity\User;

final readonly class LoginResponse implements CommandResponse
{
    public function __construct(
        public User $user,
    ) {}
}
