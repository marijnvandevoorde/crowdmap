<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UpdateStatementAction
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

        $statementId = (int) $args['statementId'];
        $statement = $this->statementRepository->findById($statementId);

        if ($statement === null || $statement->mindmapId !== $mindmap->id) {
            return $this->jsonResponse($response, ['error' => 'Statement not found'], 404);
        }

        $body = json_decode((string) $request->getBody(), true);
        $content = trim($body['content'] ?? '');

        if ($content === '') {
            return $this->jsonResponse($response, ['error' => 'Content cannot be empty'], 400);
        }

        $this->statementRepository->updateContent($statementId, $content);

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
