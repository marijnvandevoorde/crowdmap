<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to create a new mindmap with a root question.
 *
 * This command is self-validating - it guarantees that all fields are valid.
 */
final readonly class CreateMindmapCommand implements Command
{
    public readonly string $question;
    public readonly int $userId;

    public function __construct(
        string $question,
        int $userId,
    ) {
        $trimmedQuestion = trim($question);

        if ($trimmedQuestion === '') {
            throw ValidationException::emptyContent();
        }

        if ($userId < 1) {
            throw ValidationException::invalidInput('User ID must be a positive integer');
        }

        $this->question = $trimmedQuestion;
        $this->userId = $userId;
    }
}
