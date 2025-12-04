<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Mindmap;

use Crowdmap\Application\CommandResponse\CommandResponse;

final readonly class ListMindmapsResponse implements CommandResponse
{
    /**
     * @param array<array{mindmap: \Crowdmap\Entity\Mindmap, question: ?string}> $mindmaps
     */
    public function __construct(
        public array $mindmaps,
    ) {}
}
