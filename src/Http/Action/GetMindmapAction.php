<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetMindmapAction
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $mindmap = $this->mindmapRepository->findByUuid($args['uuid']);

        if ($mindmap === null) {
            return $this->jsonResponse($response, ['error' => 'Mindmap not found'], 404);
        }

        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->id);

        $statements = array_map(function ($item) {
            return [
                'id' => $item['statement']->id,
                'parentId' => $item['statement']->parentId,
                'content' => $item['statement']->content,
                'sessionId' => $item['statement']->sessionId,
                'voteCount' => $item['vote_count'],
                'createdAt' => $item['statement']->createdAt->format('c'),
            ];
        }, $statementsWithVotes);

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $mindmap->id,
                'uuid' => $mindmap->uuid,
                'userId' => $mindmap->userId,
                'focusedStatementId' => $mindmap->focusedStatementId,
                'createdAt' => $mindmap->createdAt->format('c'),
            ],
            'statements' => $statements,
        ]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
