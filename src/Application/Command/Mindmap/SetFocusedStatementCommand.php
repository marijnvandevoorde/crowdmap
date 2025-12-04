<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Mindmap;

use Crowdmap\Application\Command\Command;

/**
 * Command to set or clear the focused statement on a mindmap.
 */
final readonly class SetFocusedStatementCommand implements Command
{
    public function __construct(
        public string $mindmapUuid,
        public ?int $statementId,
        public int $userId,
    ) {}
}
