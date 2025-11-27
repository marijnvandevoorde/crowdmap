<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListMindmapsAction
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

        $mindmaps = $this->mindmapRepository->findByUserId($_SESSION['user_id']);

        $result = array_map(function ($mindmap) {
            $rootStatement = $this->statementRepository->findRootByMindmapId($mindmap->id);
            return [
                'id' => $mindmap->id,
                'uuid' => $mindmap->uuid,
                'question' => $rootStatement?->content,
                'createdAt' => $mindmap->createdAt->format('c'),
            ];
        }, $mindmaps);

        return $this->jsonResponse($response, ['mindmaps' => $result]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
