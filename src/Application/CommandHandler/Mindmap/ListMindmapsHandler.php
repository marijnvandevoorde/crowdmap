<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\ListMindmapsCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the ListMindmapsCommand.
 *
 * @implements CommandHandler<ListMindmapsCommand>
 */
final class ListMindmapsHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param ListMindmapsCommand $command
     * @return array List of mindmaps with their root questions
     */
    public function handle(Command $command): array
    {
        $mindmaps = $this->mindmapRepository->findByUserId($command->userId);

        return array_map(function ($mindmap) {
            $rootStatement = $this->statementRepository->findRootByMindmapId($mindmap->id);
            return [
                'mindmap' => $mindmap,
                'question' => $rootStatement?->content,
            ];
        }, $mindmaps);
    }
}
