<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Auth\RegisterCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EmailAlreadyExistsException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class RegisterAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        try {
            $command = new RegisterCommand(
                email: $data['email'] ?? '',
                name: $data['name'] ?? '',
                password: $data['password'] ?? '',
            );

            $result = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (EmailAlreadyExistsException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 409);
        }

        session_start();
        $_SESSION['user_id'] = $result->user->id;

        return $this->jsonResponse($response, [
            'user' => [
                'id' => $result->user->id,
                'email' => $result->user->email,
                'name' => $result->user->name,
                'mfaEnabled' => $result->user->mfaEnabled,
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
