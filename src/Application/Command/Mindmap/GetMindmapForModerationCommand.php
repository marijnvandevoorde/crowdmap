<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Mindmap;

use Crowdmap\Application\Command\Command;

/**
 * Command to retrieve a mindmap with its statements for moderation.
 */
final readonly class GetMindmapForModerationCommand implements Command
{
    public function __construct(
        public string $mindmapUuid,
        public int $userId,
    ) {}
}
