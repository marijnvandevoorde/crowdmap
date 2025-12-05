<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Statement\AddStatementResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidParentStatementException;
use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;
use PDOException;

/**
 * Handles the AddStatementCommand.
 * Creates a new statement and auto-votes for the creator.
 *
 * @implements CommandHandler<AddStatementCommand, AddStatementResponse>
 */
final class AddStatementHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
        private readonly VoteRepository $voteRepository,
    ) {}

    /**
     * @param AddStatementCommand $command
     */
    public function handle(Command $command): AddStatementResponse
    {
        // Command is self-validating, so we can trust it's valid
        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        $parentStatement = $this->statementRepository->findById($command->parentId);

        if ($parentStatement === null || $parentStatement->mindmapId !== $mindmap->id) {
            throw InvalidParentStatementException::create($command->parentId);
        }

        $statement = Statement::createReaction(
            $mindmap->id,
            $command->parentId,
            $command->content,
            $command->sessionId,
        );

        try {
            // Try to insert the statement
            $statement = $this->statementRepository->save($statement);

            // Auto-vote for the creator
            $vote = Vote::create($statement->id, $command->sessionId);
            $this->voteRepository->save($vote);
        } catch (PDOException $e) {
            // Check if it's a unique constraint violation (SQLite error code 19 for CONSTRAINT)
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'idx_statements_parent_content')) {
                // Statement already exists, find it and add a vote instead
                $existingStatement = $this->statementRepository->findByParentIdAndContent(
                    $command->parentId,
                    $command->content
                );

                if ($existingStatement === null) {
                    // This shouldn't happen, but re-throw if it does
                    throw $e;
                }

                // Check if user already voted for this statement
                $existingVote = $this->voteRepository->findByStatementAndSession(
                    $existingStatement->id,
                    $command->sessionId
                );

                // Only add vote if user hasn't voted yet
                if ($existingVote === null) {
                    $vote = Vote::create($existingStatement->id, $command->sessionId);
                    $this->voteRepository->save($vote);
                }

                $statement = $existingStatement;
            } else {
                // Different error, re-throw
                throw $e;
            }
        }

        return new AddStatementResponse($statement);
    }
}
