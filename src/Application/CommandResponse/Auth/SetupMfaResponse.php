<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Auth;

use Crowdmap\Application\CommandResponse\CommandResponse;

final readonly class SetupMfaResponse implements CommandResponse
{
    public function __construct(
        public string $secret,
        public string $otpauthUri,
    ) {}
}
