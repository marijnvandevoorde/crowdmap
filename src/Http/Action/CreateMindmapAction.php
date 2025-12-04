<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateMindmapAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Not authenticated'], 401);
        }

        $data = $request->getParsedBody();

        $command = new CreateMindmapCommand(
            question: $data['question'] ?? '',
            userId: $_SESSION['user_id'],
        );

        try {
            $result = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $result->mindmap->id,
                'uuid' => $result->mindmap->uuid,
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
