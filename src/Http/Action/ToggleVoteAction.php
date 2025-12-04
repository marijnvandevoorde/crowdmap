<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Vote\SetVoteCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\CannotVoteOnOwnStatementException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ToggleVoteAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();

        $command = new SetVoteCommand(
            statementId: (int) $args['id'],
            sessionId: $data['sessionId'] ?? '',
            vote: isset($data['vote']) ? (int) $data['vote'] : null,
        );

        try {
            $result = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (CannotVoteOnOwnStatementException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        return $this->jsonResponse($response, [
            'voted' => $result->voted,
            'voteCount' => $result->voteCount,
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
