<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Mindmap;

use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Entity\Mindmap;

final readonly class GetMindmapForModerationResponse implements CommandResponse
{
    /**
     * @param array<array{statement: \Crowdmap\Entity\Statement, vote_count: int}> $statementsWithVotes
     */
    public function __construct(
        public Mindmap $mindmap,
        public array $statementsWithVotes,
    ) {}
}
