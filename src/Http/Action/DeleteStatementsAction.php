<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\CannotDeleteRootException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteStatementsAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MindmapRepository $mindmapRepository,
        private readonly StatementRepository $statementRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $body = json_decode((string) $request->getBody(), true);

        $command = new DeleteStatementsCommand(
            mindmapUuid: $args['uuid'],
            statementIds: $body['statementIds'] ?? [],
            userId: $_SESSION['user_id'],
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (UnauthorizedException) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (ValidationException | CannotDeleteRootException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        // Return updated statements list
        $mindmap = $this->mindmapRepository->findByUuid($args['uuid']);
        $statementsWithVotes = $this->statementRepository->findByMindmapIdWithVoteCounts($mindmap->id);
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
