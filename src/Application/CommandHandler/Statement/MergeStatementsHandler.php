<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Statement\MergeStatementsCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\StatementsNotSiblingsException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the MergeStatementsCommand.
 *
 * @implements CommandHandler<MergeStatementsCommand>
 */
final class MergeStatementsHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param MergeStatementsCommand $command
     * @return Statement The merged statement
     */
    public function handle(Command $command): Statement
    {
        if (count($command->statementIds) < 2) {
            throw ValidationException::invalidInput('At least 2 statement IDs required');
        }

        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        if ($mindmap->userId !== $command->userId) {
            throw UnauthorizedException::notOwner();
        }

        // Verify all statements belong to this mindmap and collect them
        $statements = [];
        foreach ($command->statementIds as $statementId) {
            $statement = $this->statementRepository->findById((int) $statementId);

            if ($statement === null || $statement->mindmapId !== $mindmap->id) {
                throw EntityNotFoundException::statementNotFound((int) $statementId);
            }

            $statements[] = $statement;
        }

        // Verify all statements are siblings (same parent)
        $parentIds = array_unique(array_map(fn(Statement $s) => $s->parentId, $statements));
        if (count($parentIds) !== 1) {
            throw StatementsNotSiblingsException::create();
        }

        return $this->statementRepository->merge(
            array_map('intval', $command->statementIds)
        );
    }
}
