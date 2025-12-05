<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to delete one or more statements and their descendants.
 *
 * This command is self-validating - it guarantees that all fields are valid.
 */
final readonly class DeleteStatementsCommand implements Command
{
    public readonly string $mindmapUuid;
    /** @var int[] */
    public readonly array $statementIds;
    public readonly int $userId;

    /**
     * @param string $mindmapUuid
     * @param int[] $statementIds
     * @param int $userId
     */
    public function __construct(
        string $mindmapUuid,
        array $statementIds,
        int $userId,
    ) {
        $trimmedMindmapUuid = trim($mindmapUuid);

        if ($trimmedMindmapUuid === '') {
            throw ValidationException::invalidInput('Mindmap UUID is required');
        }

        if (empty($statementIds)) {
            throw ValidationException::invalidInput('At least 1 statement ID required');
        }

        foreach ($statementIds as $statementId) {
            if (!is_int($statementId) || $statementId < 1) {
                throw ValidationException::invalidInput('All statement IDs must be positive integers');
            }
        }

        if ($userId < 1) {
            throw ValidationException::invalidInput('User ID must be a positive integer');
        }

        $this->mindmapUuid = $trimmedMindmapUuid;
        $this->statementIds = $statementIds;
        $this->userId = $userId;
    }
}
