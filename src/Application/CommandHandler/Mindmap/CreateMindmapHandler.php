<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Mindmap\CreateMindmapResponse;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the CreateMindmapCommand.
 *
 * @implements CommandHandler<CreateMindmapCommand, CreateMindmapResponse>
 */
final class CreateMindmapHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param CreateMindmapCommand $command
     */
    public function handle(Command $command): CreateMindmapResponse
    {
        // Command is self-validating, so we can trust it's valid
        $mindmap = Mindmap::create($command->userId);
        $mindmap = $this->mindmapRepository->save($mindmap);

        $rootStatement = Statement::createRoot($mindmap->id, $command->question);
        $this->statementRepository->save($rootStatement);

        return new CreateMindmapResponse($mindmap);
    }
}
