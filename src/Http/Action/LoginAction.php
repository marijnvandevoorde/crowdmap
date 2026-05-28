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

        try {
            $command = new LoginCommand(
                email: $data['email'] ?? '',
                password: $data['password'] ?? '',
            );

            $result = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (InvalidCredentialsException) {
            return $this->jsonResponse($response, ['error' => 'Invalid credentials'], 401);
        }

        session_start();

        if ($result->user->mfaEnabled) {
            unset($_SESSION['user_id']);
            $_SESSION['mfa_pending_user_id'] = $result->user->id;

            return $this->jsonResponse($response, ['mfaRequired' => true]);
        }

        unset($_SESSION['mfa_pending_user_id']);
        $_SESSION['user_id'] = $result->user->id;

        return $this->jsonResponse($response, [
            'user' => [
                'id' => $result->user->id,
                'email' => $result->user->email,
                'name' => $result->user->name,
                'mfaEnabled' => $result->user->mfaEnabled,
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
