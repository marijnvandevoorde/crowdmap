<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;

/**
 * Command to delete one or more statements and their descendants.
 */
final readonly class DeleteStatementsCommand implements Command
{
    /**
     * @param string $mindmapUuid
     * @param int[] $statementIds
     * @param int $userId
     */
    public function __construct(
        public string $mindmapUuid,
        public array $statementIds,
        public int $userId,
    ) {}
}
