<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MergeStatementsAction
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $mindmap = $this->mindmapRepository->findByUuid($args['uuid']);

        if ($mindmap === null) {
            return $this->jsonResponse($response, ['error' => 'Mindmap not found'], 404);
        }

        if ($mindmap->userId !== $_SESSION['user_id']) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        }

        $body = json_decode((string) $request->getBody(), true);
        $statementIds = $body['statementIds'] ?? [];

        if (!is_array($statementIds) || count($statementIds) < 2) {
            return $this->jsonResponse($response, ['error' => 'At least 2 statement IDs required'], 400);
        }

        // Verify all statements belong to this mindmap
        foreach ($statementIds as $statementId) {
            $statement = $this->statementRepository->findById((int) $statementId);
            if ($statement === null || $statement->mindmapId !== $mindmap->id) {
                return $this->jsonResponse($response, ['error' => 'Statement not found or does not belong to this mindmap'], 404);
            }
        }

        try {
            $this->statementRepository->merge(array_map('intval', $statementIds));
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        // Return updated statements list
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

        return $this->jsonResponse($response, ['statements' => $statements]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
