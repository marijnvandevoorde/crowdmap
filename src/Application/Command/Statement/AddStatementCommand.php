<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to add a new statement (reaction) to a mindmap.
 * This is a public action - no authentication required.
 *
 * This command is self-validating - it guarantees that all fields are valid.
 */
final readonly class AddStatementCommand implements Command
{
    public readonly string $mindmapUuid;
    public readonly int $parentId;
    public readonly string $content;
    public readonly string $sessionId;

    public function __construct(
        string $mindmapUuid,
        int $parentId,
        string $content,
        string $sessionId,
    ) {
        $trimmedMindmapUuid = trim($mindmapUuid);
        $trimmedContent = trim($content);
        $trimmedSessionId = trim($sessionId);

        if ($trimmedMindmapUuid === '') {
            throw ValidationException::invalidInput('Mindmap UUID is required');
        }

        if ($trimmedContent === '') {
            throw ValidationException::emptyContent();
        }

        if ($trimmedSessionId === '') {
            throw ValidationException::invalidInput('Session ID is required');
        }

        if ($parentId < 1) {
            throw ValidationException::invalidInput('Parent ID must be a positive integer');
        }

        $this->mindmapUuid = $trimmedMindmapUuid;
        $this->parentId = $parentId;
        $this->content = $trimmedContent;
        $this->sessionId = $trimmedSessionId;
    }
}
