<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Statement;

use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Entity\Statement;

final readonly class AddStatementResponse implements CommandResponse
{
    public function __construct(
        public Statement $statement,
    ) {}
}
