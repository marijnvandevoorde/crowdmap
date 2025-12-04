<?php

declare(strict_types=1);

namespace Crowdmap\Application\Command\Vote;

use Crowdmap\Application\Command\Command;

/**
 * Command to set or toggle a vote on a statement.
 */
final readonly class SetVoteCommand implements Command
{
    public function __construct(
        public int $statementId,
        public string $sessionId,
        public ?int $vote = null,
    ) {}
}
