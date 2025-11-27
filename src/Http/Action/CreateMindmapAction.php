<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CreateMindmapAction
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Not authenticated'], 401);
        }

        $data = $request->getParsedBody();
        $question = trim($data['question'] ?? '');

        if (empty($question)) {
            return $this->jsonResponse($response, ['error' => 'Question is required'], 400);
        }

        $mindmap = Mindmap::create($_SESSION['user_id']);
        $mindmap = $this->mindmapRepository->save($mindmap);

        $rootStatement = Statement::createRoot($mindmap->id, $question);
        $this->statementRepository->save($rootStatement);

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $mindmap->id,
                'uuid' => $mindmap->uuid,
            ],
        ], 201);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
