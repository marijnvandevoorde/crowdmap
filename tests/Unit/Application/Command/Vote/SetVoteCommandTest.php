<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Vote;

use Crowdmap\Application\Command\Vote\SetVoteCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class SetVoteCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputsAndNullVote(): void
    {
        $command = new SetVoteCommand(
            statementId: 5,
            sessionId: 'session123'
        );

        $this->assertSame(5, $command->statementId);
        $this->assertSame('session123', $command->sessionId);
        $this->assertNull($command->vote);
    }

    public function testCreatesCommandWithVoteSetToOne(): void
    {
        $command = new SetVoteCommand(
            statementId: 5,
            sessionId: 'session123',
            vote: 1
        );

        $this->assertSame(5, $command->statementId);
        $this->assertSame('session123', $command->sessionId);
        $this->assertSame(1, $command->vote);
    }

    public function testCreatesCommandWithVoteSetToZero(): void
    {
        $command = new SetVoteCommand(
            statementId: 5,
            sessionId: 'session123',
            vote: 0
        );

        $this->assertSame(5, $command->statementId);
        $this->assertSame('session123', $command->sessionId);
        $this->assertSame(0, $command->vote);
    }

    public function testTrimsSessionId(): void
    {
        $command = new SetVoteCommand(
            statementId: 5,
            sessionId: '  session123  '
        );

        $this->assertSame('session123', $command->sessionId);
    }

    public function testThrowsValidationExceptionWhenStatementIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Statement ID must be a positive integer');

        new SetVoteCommand(
            statementId: 0,
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Statement ID must be a positive integer');

        new SetVoteCommand(
            statementId: -1,
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenSessionIdIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID is required');

        new SetVoteCommand(
            statementId: 5,
            sessionId: ''
        );
    }

    public function testThrowsValidationExceptionWhenSessionIdIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID is required');

        new SetVoteCommand(
            statementId: 5,
            sessionId: '   '
        );
    }

    public function testThrowsValidationExceptionWhenVoteIsInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Vote must be null, 0, or 1');

        new SetVoteCommand(
            statementId: 5,
            sessionId: 'session123',
            vote: 2
        );
    }

    public function testThrowsValidationExceptionWhenVoteIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Vote must be null, 0, or 1');

        new SetVoteCommand(
            statementId: 5,
            sessionId: 'session123',
            vote: -1
        );
    }
}
