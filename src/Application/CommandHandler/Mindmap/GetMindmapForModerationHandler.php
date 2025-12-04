<?php

declare(strict_types=1);

namespace Crowdmap\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Command;
use Crowdmap\Application\Command\Mindmap\GetMindmapForModerationCommand;
use Crowdmap\Application\CommandHandler\CommandHandler;
use Crowdmap\Application\CommandResponse\Mindmap\GetMindmapForModerationResponse;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;

/**
 * Handles the GetMindmapForModerationCommand.
 *
 * @implements CommandHandler<GetMindmapForModerationCommand, GetMindmapForModerationResponse>
 */
final class GetMindmapForModerationHandler implements CommandHandler
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    /**
     * @param GetMindmapForModerationCommand $command
     */
    public function handle(Command $command): GetMindmapForModerationResponse
    {
        $mindmap = $this->mindmapRepository->findByUuid($command->mindmapUuid);

        if ($mindmap === null) {
            throw EntityNotFoundException::mindmapNotFound($command->mindmapUuid);
        }

        if ($mindmap->userId !== $command->userId) {
            throw UnauthorizedException::notOwner();
        }

        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->id);

        return new GetMindmapForModerationResponse($mindmap, $statementsWithVotes);
    }
}
