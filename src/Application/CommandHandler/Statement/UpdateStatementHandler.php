<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\CommandResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the UpdateStatementCommand.
 *
 * @implements CommandHandler<UpdateStatementCommand, null>
 */
final class UpdateStatementHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param UpdateStatementCommand $command
     */
    public function handle(Command $command): ?CommandResponse
    {
        // Command is self-validating, so we can trust it's valid
        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        if ($mindmap->userId !== $command->userId) {
            throw UnauthorizedException::notOwner();
        }

        $statement = $this->statementRepository->findById($command->statementId);

        if ($statement === null || $statement->mindmapId !== $mindmap->id) {
            throw EntityNotFoundException::statementNotFound($command->statementId);
        }

        $this->statementRepository->updateContent($command->statementId, $command->content);

        return null;
    }
}
