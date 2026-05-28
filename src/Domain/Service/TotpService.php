<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Service;

use OTPHP\TOTP;

/**
 * Thin wrapper around the otphp TOTP implementation.
 */
final class TotpService
{
    private const ISSUER = 'Crowdmap';

    // Accept codes from the adjacent time window to tolerate clock drift (seconds, must be < period).
    private const VERIFY_LEEWAY = 29;

    public function generateSecret(): string
    {
        return TOTP::generate()->getSecret();
    }

    public function provisioningUri(string $secret, string $accountLabel): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($accountLabel);
        $totp->setIssuer(self::ISSUER);

        return $totp->getProvisioningUri();
    }

    public function verify(string $secret, string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        return TOTP::createFromSecret($secret)->verify($code, null, self::VERIFY_LEEWAY);
    }
}
