<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Auth;

use Crowdmap\Application\Command\Auth\RegisterCommand;
use Crowdmap\Application\CommandHandler\Auth\RegisterHandler;
use Crowdmap\Domain\Exception\EmailAlreadyExistsException;
use Crowdmap\Entity\User;
use Crowdmap\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class RegisterHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private RegisterHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new RegisterHandler($this->userRepository);
    }

    public function testRegistersNewUser(): void
    {
        $command = new RegisterCommand('new@example.com', 'New User', 'password123');

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('new@example.com')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn (User $user): User => $user->withId(42));

        $response = $this->handler->handle($command);

        $this->assertSame(42, $response->user->id);
        $this->assertSame('new@example.com', $response->user->email);
        $this->assertSame('New User', $response->user->name);
        $this->assertFalse($response->user->mfaEnabled);
        $this->assertTrue($response->user->verifyPassword('password123'));
    }

    public function testThrowsWhenEmailAlreadyExists(): void
    {
        $command = new RegisterCommand('taken@example.com', 'Someone', 'password123');

        $existing = User::create('taken@example.com', 'Existing', 'password123')->withId(1);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('taken@example.com')
            ->willReturn($existing);

        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(EmailAlreadyExistsException::class);

        $this->handler->handle($command);
    }
}
