<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Vote;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Vote\SetVoteCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\CannotVoteOnOwnStatementException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;

/**
 * Handles the SetVoteCommand.
 *
 * @implements CommandHandler<SetVoteCommand>
 */
final class SetVoteHandler implements CommandHandler
{
    public function __construct(
        private readonly StatementRepository $statementRepository,
        private readonly VoteRepository $voteRepository,
    ) {}

    /**
     * @param SetVoteCommand $command
     * @return array{voted: bool, voteCount: int}
     */
    public function handle(Command $command): array
    {
        if (trim($command->sessionId) === '') {
            throw ValidationException::invalidInput('Session ID is required');
        }

        $statement = $this->statementRepository->findById($command->statementId);

        if ($statement === null) {
            throw EntityNotFoundException::statementNotFound($command->statementId);
        }

        if ($statement->sessionId === $command->sessionId) {
            throw CannotVoteOnOwnStatementException::create();
        }

        $existingVote = $this->voteRepository->findByStatementAndSession($command->statementId, $command->sessionId);
        $hasVote = $existingVote !== null;

        // Determine desired vote state
        if ($command->vote !== null) {
            // Explicit vote value: 1 = vote, 0 = no vote
            $shouldHaveVote = $command->vote === 1;
        } else {
            // Toggle behavior
            $shouldHaveVote = !$hasVote;
        }

        // Apply the change if needed
        if ($shouldHaveVote && !$hasVote) {
            $vote = Vote::create($command->statementId, $command->sessionId);
            $this->voteRepository->save($vote);
        } elseif (!$shouldHaveVote && $hasVote) {
            $this->voteRepository->delete($command->statementId, $command->sessionId);
        }

        $voteCount = $this->voteRepository->countByStatementId($command->statementId);

        return [
            'voted' => $shouldHaveVote,
            'voteCount' => $voteCount,
        ];
    }
}
