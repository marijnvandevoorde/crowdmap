<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidParentStatementException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AddStatementAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();

        $command = new AddStatementCommand(
            mindmapUuid: $args['uuid'],
            parentId: (int) ($data['parentId'] ?? 0),
            content: $data['content'] ?? '',
            sessionId: $data['sessionId'] ?? '',
        );

        try {
            $result = $this->commandBus->dispatch($command);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (ValidationException | InvalidParentStatementException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        return $this->jsonResponse($response, [
            'statement' => [
                'id' => $result->statement->id,
                'parentId' => $result->statement->parentId,
                'content' => $result->statement->content,
                'sessionId' => $result->statement->sessionId,
                'voteCount' => 1,
                'createdAt' => $result->statement->createdAt->format('c'),
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
