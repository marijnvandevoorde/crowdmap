<?php

declare(strict_types=1);

namespace Crowdmap\Database;

interface Migration
{
    public function up(DatabaseConnection $connection): void;

    public function down(DatabaseConnection $connection): void;
}
