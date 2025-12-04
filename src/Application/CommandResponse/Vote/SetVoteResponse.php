<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Vote;

use Crowdmap\Application\CommandResponse\CommandResponse;

final readonly class SetVoteResponse implements CommandResponse
{
    public function __construct(
        public bool $voted,
        public int $voteCount,
    ) {}
}
