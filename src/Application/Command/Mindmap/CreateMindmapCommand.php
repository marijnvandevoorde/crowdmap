<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Mindmap;

use Crowdmap\Application\Command\Command;

/**
 * Command to create a new mindmap with a root question.
 */
final readonly class CreateMindmapCommand implements Command
{
    public function __construct(
        public string $question,
        public int $userId,
    ) {}
}
