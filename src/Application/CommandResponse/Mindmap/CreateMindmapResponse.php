<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandResponse\Mindmap;

use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Entity\Mindmap;

final readonly class CreateMindmapResponse implements CommandResponse
{
    public function __construct(
        public Mindmap $mindmap,
    ) {}
}
