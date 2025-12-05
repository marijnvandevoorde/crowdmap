<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Vote;

use Crowdmap\Application\Command\Command;
use Crowdmap\Domain\Exception\ValidationException;

/**
 * Command to set or toggle a vote on a statement.
 *
 * This command is self-validating - it guarantees that all fields are valid.
 */
final readonly class SetVoteCommand implements Command
{
    public readonly int $statementId;
    public readonly string $sessionId;
    public readonly ?int $vote;

    public function __construct(
        int $statementId,
        string $sessionId,
        ?int $vote = null,
    ) {
        $trimmedSessionId = trim($sessionId);

        if ($statementId < 1) {
            throw ValidationException::invalidInput('Statement ID must be a positive integer');
        }

        if ($trimmedSessionId === '') {
            throw ValidationException::invalidInput('Session ID is required');
        }

        if ($vote !== null && $vote !== 0 && $vote !== 1) {
            throw ValidationException::invalidInput('Vote must be null, 0, or 1');
        }

        $this->statementId = $statementId;
        $this->sessionId = $trimmedSessionId;
        $this->vote = $vote;
    }
}
