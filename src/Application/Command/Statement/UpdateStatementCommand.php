<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to update the content of a statement.
 *
 * This command is self-validating - it guarantees that all fields are valid.
 */
final readonly class UpdateStatementCommand implements Command
{
    public readonly string $mindmapUuid;
    public readonly int $statementId;
    public readonly string $content;
    public readonly int $userId;

    public function __construct(
        string $mindmapUuid,
        int $statementId,
        string $content,
        int $userId,
    ) {
        $trimmedMindmapUuid = trim($mindmapUuid);
        $trimmedContent = trim($content);

        if ($trimmedMindmapUuid === '') {
            throw ValidationException::invalidInput('Mindmap UUID is required');
        }

        if ($statementId < 1) {
            throw ValidationException::invalidInput('Statement ID must be a positive integer');
        }

        if ($trimmedContent === '') {
            throw ValidationException::emptyContent();
        }

        if ($userId < 1) {
            throw ValidationException::invalidInput('User ID must be a positive integer');
        }

        $this->mindmapUuid = $trimmedMindmapUuid;
        $this->statementId = $statementId;
        $this->content = $trimmedContent;
        $this->userId = $userId;
    }
}
