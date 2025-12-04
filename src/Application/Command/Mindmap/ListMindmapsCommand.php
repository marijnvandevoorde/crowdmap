<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Mindmap;

use Crowdmap\Application\Command\Command;

/**
 * Command to list all mindmaps for a user.
 */
final readonly class ListMindmapsCommand implements Command
{
    public function __construct(
        public int $userId,
    ) {}
}
