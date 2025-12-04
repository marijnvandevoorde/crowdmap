<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the CreateMindmapCommand.
 *
 * @implements CommandHandler<CreateMindmapCommand>
 */
final class CreateMindmapHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param CreateMindmapCommand $command
     * @return Mindmap The created mindmap
     */
    public function handle(Command $command): Mindmap
    {
        $question = trim($command->question);

        if ($question === '') {
            throw ValidationException::emptyContent();
        }

        $mindmap = Mindmap::create($command->userId);
        $mindmap = $this->mindmapRepository->save($mindmap);

        $rootStatement = Statement::createRoot($mindmap->id, $question);
        $this->statementRepository->save($rootStatement);

        return $mindmap;
    }
}
