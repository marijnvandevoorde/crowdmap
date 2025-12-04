<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\ListMindmapsCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Mindmap\ListMindmapsResponse;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the ListMindmapsCommand.
 *
 * @implements CommandHandler<ListMindmapsCommand, ListMindmapsResponse>
 */
final class ListMindmapsHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param ListMindmapsCommand $command
     */
    public function handle(Command $command): ListMindmapsResponse
    {
        $mindmaps = $this->mindmapRepository->findByUserId($command->userId);

        $items = array_map(function ($mindmap) {
            $rootStatement = $this->statementRepository->findRootByMindmapId($mindmap->id);
            return [
                'mindmap' => $mindmap,
                'question' => $rootStatement?->content,
            ];
        }, $mindmaps);

        return new ListMindmapsResponse($items);
    }
}
