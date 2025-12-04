<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\InvalidCredentialsException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LoginAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $command = new LoginCommand(
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
        );

        try {
            $user = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (InvalidCredentialsException) {
            return $this->jsonResponse($response, ['error' => 'Invalid credentials'], 401);
        }

        session_start();
        $_SESSION['user_id'] = $user->id;

        return $this->jsonResponse($response, [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
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
