<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Auth;

use Crowdmap\Application\CommandResponse\CommandResponse;

final readonly class ConfirmMfaResponse implements CommandResponse
{
    /**
     * @param string[] $recoveryCodes plaintext recovery codes, shown once
     */
    public function __construct(
        public array $recoveryCodes,
    ) {}
}
