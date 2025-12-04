<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Statement\AddStatementResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidParentStatementException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;

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
        $content = trim($command->content);

        if ($content === '') {
            throw ValidationException::emptyContent();
        }

        if ($command->sessionId === '') {
            throw ValidationException::invalidInput('Session ID is required');
        }

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
            $content,
            $command->sessionId,
        );
        $statement = $this->statementRepository->save($statement);

        // Auto-vote for the creator
        $vote = Vote::create($statement->id, $command->sessionId);
        $this->voteRepository->save($vote);

        return new AddStatementResponse($statement);
    }
}
