<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Auth\DisableMfaCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DisableMfaAction
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

        try {
            $command = new DisableMfaCommand($_SESSION['user_id'], $data['password'] ?? '');
            $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (InvalidCredentialsException) {
            return $this->jsonResponse($response, ['error' => 'Invalid password'], 401);
        } catch (EntityNotFoundException) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 401);
        }

        return $this->jsonResponse($response, ['success' => true]);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
