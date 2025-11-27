<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Entity\Vote;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ToggleVoteAction
{
    public function __construct(
        private readonly StatementRepository $statementRepository,
        private readonly VoteRepository $voteRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $statementId = (int) $args['id'];

        $statement = $this->statementRepository->findById($statementId);
        if ($statement === null) {
            return $this->jsonResponse($response, ['error' => 'Statement not found'], 404);
        }

        $data = $request->getParsedBody();
        $sessionId = $data['sessionId'] ?? null;

        if (empty($sessionId)) {
            return $this->jsonResponse($response, ['error' => 'Session ID is required'], 400);
        }

        // Cannot vote on own statement
        if ($statement->sessionId === $sessionId) {
            return $this->jsonResponse($response, ['error' => 'Cannot vote on own statement'], 400);
        }

        $existingVote = $this->voteRepository->findByStatementAndSession($statementId, $sessionId);

        if ($existingVote !== null) {
            // Remove vote
            $this->voteRepository->delete($statementId, $sessionId);
            $voted = false;
        } else {
            // Add vote
            $vote = Vote::create($statementId, $sessionId);
            $this->voteRepository->save($vote);
            $voted = true;
        }

        $voteCount = $this->voteRepository->countByStatementId($statementId);

        return $this->jsonResponse($response, [
            'voted' => $voted,
            'voteCount' => $voteCount,
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
