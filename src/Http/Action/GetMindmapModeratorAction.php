<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Mindmap\GetMindmapForModerationCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetMindmapModeratorAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Not authenticated'], 401);
        }

        $command = new GetMindmapForModerationCommand(
            mindmapUuid: $args['uuid'],
            userId: $_SESSION['user_id'],
        );

        try {
            $result = $this->commandBus->dispatch($command);
        } catch (EntityNotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (UnauthorizedException) {
            return $this->jsonResponse($response, ['error' => 'Not authorized'], 403);
        }

        $statements = array_map(function ($item) {
            return [
                'id' => $item['statement']->id,
                'parentId' => $item['statement']->parentId,
                'content' => $item['statement']->content,
                'voteCount' => $item['vote_count'],
                'createdAt' => $item['statement']->createdAt->format('c'),
            ];
        }, $result->statementsWithVotes);

        $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/');

        return $this->jsonResponse($response, [
            'mindmap' => [
                'id' => $result->mindmap->id,
                'uuid' => $result->mindmap->uuid,
                'userId' => $result->mindmap->userId,
                'focusedStatementId' => $result->mindmap->focusedStatementId,
                'createdAt' => $result->mindmap->createdAt->format('c'),
            ],
            'statements' => $statements,
            'contributorUrl' => $appUrl . '/contribute/' . $result->mindmap->uuid,
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
