<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AddStatementAction
{
    public function __construct(
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
        private readonly VoteRepository $voteRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $mindmap = $this->mindmapRepository->findByUuid($args['uuid']);

        if ($mindmap === null) {
            return $this->jsonResponse($response, ['error' => 'Mindmap not found'], 404);
        }

        $data = $request->getParsedBody();
        $content = trim($data['content'] ?? '');
        $parentId = $data['parentId'] ?? null;
        $sessionId = $data['sessionId'] ?? null;

        if (empty($content)) {
            return $this->jsonResponse($response, ['error' => 'Content is required'], 400);
        }

        if (empty($parentId)) {
            return $this->jsonResponse($response, ['error' => 'Parent ID is required'], 400);
        }

        if (empty($sessionId)) {
            return $this->jsonResponse($response, ['error' => 'Session ID is required'], 400);
        }

        $parentStatement = $this->statementRepository->findById((int) $parentId);
        if ($parentStatement === null || $parentStatement->mindmapId !== $mindmap->id) {
            return $this->jsonResponse($response, ['error' => 'Invalid parent statement'], 400);
        }

        $statement = Statement::createReaction($mindmap->id, (int) $parentId, $content, $sessionId);
        $statement = $this->statementRepository->save($statement);

        // Auto-vote for the creator
        $vote = Vote::create($statement->id, $sessionId);
        $this->voteRepository->save($vote);

        return $this->jsonResponse($response, [
            'statement' => [
                'id' => $statement->id,
                'parentId' => $statement->parentId,
                'content' => $statement->content,
                'sessionId' => $statement->sessionId,
                'voteCount' => 1,
                'createdAt' => $statement->createdAt->format('c'),
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
