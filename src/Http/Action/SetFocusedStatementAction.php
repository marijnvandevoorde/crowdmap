<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Mindmap\SetFocusedStatementCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SetFocusedStatementAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Unauthorized'], 401);
        }

        $body = json_decode((string) $request->getBody(), true);
        $statementId = $body['statementId'] ?? null;

        $command = new SetFocusedStatementCommand(
            mindmapUuid: $args['uuid'],
            statementId: $statementId,
            userId: $_SESSION['user_id'],
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (UnauthorizedException) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'focusedStatementId' => $statementId,
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
