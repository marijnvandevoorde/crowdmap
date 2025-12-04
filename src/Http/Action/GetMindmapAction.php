<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Mindmap\GetMindmapCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetMindmapAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $command = new GetMindmapCommand(
            mindmapUuid: $args['uuid'],
        );

        try {
            $result = $this->commandBus->dispatch($command);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        }

        $mindmap = $result['mindmap'];
        $statements = array_map(function ($item) {
            return [
                'id' => $item['statement']->id,
                'parentId' => $item['statement']->parentId,
                'content' => $item['statement']->content,
                'sessionId' => $item['statement']->sessionId,
                'voteCount' => $item['vote_count'],
                'createdAt' => $item['statement']->createdAt->format('c'),
            ];
        }, $result['statements']);

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $mindmap->id,
                'uuid' => $mindmap->uuid,
                'userId' => $mindmap->userId,
                'focusedStatementId' => $mindmap->focusedStatementId,
                'createdAt' => $mindmap->createdAt->format('c'),
            ],
            'statements' => $statements,
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
