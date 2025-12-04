<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;

/**
 * Command to add a new statement (reaction) to a mindmap.
 * This is a public action - no authentication required.
 */
final readonly class AddStatementCommand implements Command
{
    public function __construct(
        public string $mindmapUuid,
        public int $parentId,
        public string $content,
        public string $sessionId,
    ) {}
}
