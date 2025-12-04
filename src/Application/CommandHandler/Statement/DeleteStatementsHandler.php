<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\CannotDeleteRootException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the DeleteStatementsCommand.
 *
 * @implements CommandHandler<DeleteStatementsCommand>
 */
final class DeleteStatementsHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param DeleteStatementsCommand $command
     */
    public function handle(Command $command): mixed
    {
        if (empty($command->statementIds)) {
            throw ValidationException::invalidInput('At least 1 statement ID required');
        }

        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        if ($mindmap->userId !== $command->userId) {
            throw UnauthorizedException::notOwner();
        }

        // Verify all statements belong to this mindmap and are not root
        foreach ($command->statementIds as $statementId) {
            $statement = $this->statementRepository->findById((int) $statementId);

            if ($statement === null || $statement->mindmapId !== $mindmap->id) {
                throw EntityNotFoundException::statementNotFound((int) $statementId);
            }

            if ($statement->parentId === null) {
                throw CannotDeleteRootException::create();
            }
        }

        $this->statementRepository->deleteWithDescendants(
            array_map('intval', $command->statementIds)
        );

        return null;
    }
}
