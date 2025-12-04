<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateStatementAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $body = json_decode((string) $request->getBody(), true);

        $command = new UpdateStatementCommand(
            mindmapUuid: $args['uuid'],
            statementId: (int) $args['statementId'],
            content: $body['content'] ?? '',
            userId: $_SESSION['user_id'],
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (UnauthorizedException) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        // Return updated statements list
        // Note: This could be moved to a Query/QueryHandler in CQRS style
        $mindmap = $this->statementRepository->findById((int) $args['statementId']);
        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->mindmapId);
        $statements = array_map(fn($item) => [
            'id' => $item['statement']->id,
            'parentId' => $item['statement']->parentId,
            'content' => $item['statement']->content,
            'sessionId' => $item['statement']->sessionId,
            'voteCount' => $item['vote_count'],
            'createdAt' => $item['statement']->createdAt->format('c'),
        ], $statementsWithVotes);

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
