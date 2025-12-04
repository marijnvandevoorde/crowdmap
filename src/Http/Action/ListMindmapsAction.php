<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Mindmap\ListMindmapsCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListMindmapsAction
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

        $command = new ListMindmapsCommand(
            userId: $_SESSION['user_id'],
        );

        $result = $this->commandBus->dispatch($command);

        $mindmaps = array_map(function ($item) {
            return [
                'id' => $item['mindmap']->id,
                'uuid' => $item['mindmap']->uuid,
                'question' => $item['question'],
                'createdAt' => $item['mindmap']->createdAt->format('c'),
            ];
        }, $result);

        return $this->jsonResponse($response, ['mindmaps' => $mindmaps]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
