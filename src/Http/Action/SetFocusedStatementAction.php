<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SetFocusedStatementAction
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
        $statementId = $body['statementId'] ?? null;

        // If statementId provided, verify it belongs to this mindmap
        if ($statementId !== null) {
            $statement = $this->statementRepository->findById($statementId);
            if ($statement === null || $statement->mindmapId !== $mindmap->id) {
                return $this->jsonResponse($response, ['error' => 'Statement not found'], 404);
            }
        }

        $this->mindmapRepository->updateFocusedStatement($mindmap->id, $statementId);

        return $this->jsonResponse($response, ['success' => true, 'focusedStatementId' => $statementId]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
