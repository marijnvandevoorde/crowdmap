<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;

/**
 * Command to merge multiple sibling statements into one.
 */
final readonly class MergeStatementsCommand implements Command
{
    /**
     * @param string $mindmapUuid
     * @param int[] $statementIds At least 2 statement IDs required
     * @param int $userId
     */
    public function __construct(
        public string $mindmapUuid,
        public array $statementIds,
        public int $userId,
    ) {}
}
