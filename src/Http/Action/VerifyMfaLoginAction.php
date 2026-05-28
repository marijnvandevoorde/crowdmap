<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Application\Command\Auth\VerifyMfaLoginCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidMfaCodeException;
use Crowdmap\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class VerifyMfaLoginAction
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        session_start();

        if (!isset($_SESSION['mfa_pending_user_id'])) {
            return $this->jsonResponse($response, ['error' => 'No login in progress'], 401);
        }

        $data = $request->getParsedBody();

        try {
            $command = new VerifyMfaLoginCommand($_SESSION['mfa_pending_user_id'], $data['code'] ?? '');
            $result = $this->commandBus->dispatch($command);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        } catch (InvalidMfaCodeException) {
            return $this->jsonResponse($response, ['error' => 'Invalid verification code'], 401);
        } catch (EntityNotFoundException) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 401);
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
