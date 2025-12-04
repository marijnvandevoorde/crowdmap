<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\SetFocusedStatementCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the SetFocusedStatementCommand.
 *
 * @implements CommandHandler<SetFocusedStatementCommand>
 */
final class SetFocusedStatementHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param SetFocusedStatementCommand $command
     */
    public function handle(Command $command): mixed
    {
        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        if ($mindmap->userId !== $command->userId) {
            throw UnauthorizedException::notOwner();
        }

        // If statementId provided, verify it belongs to this mindmap
        if ($command->statementId !== null) {
            $statement = $this->statementRepository->findById($command->statementId);

            if ($statement === null || $statement->mindmapId !== $mindmap->id) {
                throw EntityNotFoundException::statementNotFound($command->statementId);
            }
        }

        $this->mindmapRepository->updateFocusedStatement($mindmap->id, $command->statementId);

        return null;
    }
}
