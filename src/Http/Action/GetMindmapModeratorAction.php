<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetMindmapModeratorAction
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Not authenticated'], 401);
        }

        $mindmap = $this->mindmapRepository->findByUuid($args['uuid']);

        if ($mindmap === null) {
            return $this->jsonResponse($response, ['error' => 'Mindmap not found'], 404);
        }

        // Check ownership
        if ($mindmap->userId !== $_SESSION['user_id']) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        }

        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->id);

        $statements = array_map(function ($item) {
            return [
                'id' => $item['statement']->id,
                'parentId' => $item['statement']->parentId,
                'content' => $item['statement']->content,
                'voteCount' => $item['vote_count'],
                'createdAt' => $item['statement']->createdAt->format('c'),
            ];
        }, $statementsWithVotes);

        $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $mindmap->id,
                'uuid' => $mindmap->uuid,
                'userId' => $mindmap->userId,
                'focusedStatementId' => $mindmap->focusedStatementId,
                'createdAt' => $mindmap->createdAt->format('c'),
            ],
            'statements' => $statements,
            'contributorUrl' => $appUrl . '/contribute/' . $mindmap->uuid,
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
