<?php

declare(strict_types=1);

namespace Crowdmap\Http\Action;

use Crowdmap\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MeAction
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse($response, ['error' => 'Not authenticated'], 401);
        }

        $user = $this->userRepository->findById($_SESSION['user_id']);

        if ($user === null) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 401);
        }

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
