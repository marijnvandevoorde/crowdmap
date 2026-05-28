<?php

declare(strict_types=1);

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\Migration;

return new class implements Migration {
    public function up(DatabaseConnection $connection): void
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $connection->execute('ALTER TABLE users ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0');
            $connection->execute('ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL');
            $connection->execute('ALTER TABLE users ADD COLUMN mfa_recovery_codes TEXT NULL');
        } else {
            $connection->execute('ALTER TABLE users ADD COLUMN mfa_enabled INTEGER NOT NULL DEFAULT 0');
            $connection->execute('ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL');
            $connection->execute('ALTER TABLE users ADD COLUMN mfa_recovery_codes TEXT NULL');
        }
    }

    public function down(DatabaseConnection $connection): void
    {
        $connection->execute('ALTER TABLE users DROP COLUMN mfa_recovery_codes');
        $connection->execute('ALTER TABLE users DROP COLUMN totp_secret');
        $connection->execute('ALTER TABLE users DROP COLUMN mfa_enabled');
    }
};
