<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;

/**
 * Command to update the content of a statement.
 */
final readonly class UpdateStatementCommand implements Command
{
    public function __construct(
        public string $mindmapUuid,
        public int $statementId,
        public string $content,
        public int $userId,
    ) {}
}
