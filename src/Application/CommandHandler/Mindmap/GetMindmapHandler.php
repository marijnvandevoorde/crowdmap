<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\GetMindmapCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the GetMindmapCommand.
 *
 * @implements CommandHandler<GetMindmapCommand>
 */
final class GetMindmapHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param GetMindmapCommand $command
     * @return array{mindmap: \Crowdmap\Entity\Mindmap, statements: array}
     */
    public function handle(Command $command): array
    {
        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->id);

        return [
            'mindmap' => $mindmap,
            'statements' => $statementsWithVotes,
        ];
    }
}
