<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Vote;

use Crowdmap\Application\Command\Vote\SetVoteCommand;
use Crowdmap\Application\CommandHandler\Vote\SetVoteHandler;
use Crowdmap\Domain\Exception\CannotVoteOnOwnStatementException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SetVoteHandlerTest extends TestCase
{
    private StatementRepository $statementRepository;
    private VoteRepository $voteRepository;
    private SetVoteHandler $handler;

    protected function setUp(): void
    {
        $this->statementRepository = $this->createMock(StatementRepository::class);
        $this->voteRepository = $this->createMock(VoteRepository::class);

        $this->handler = new SetVoteHandler(
            $this->statementRepository,
            $this->voteRepository
        );
    }

    public function testSuccessfullyAddsVoteWhenTogglingFromNoVote(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123'
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($statement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->with(1, 'session123')
            ->willReturn(null);

        $this->voteRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Vote $vote) {
                return $vote->statementId === 1 && $vote->sessionId === 'session123';
            }));

        $this->voteRepository
            ->expects($this->once())
            ->method('countByStatementId')
            ->with(1)
            ->willReturn(5);

        $response = $this->handler->handle($command);

        $this->assertTrue($response->voted);
        $this->assertSame(5, $response->voteCount);
    }

    public function testSuccessfullyRemovesVoteWhenTogglingFromExistingVote(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123'
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $existingVote = new Vote(
            id: 10,
            statementId: 1,
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->with(1, 'session123')
            ->willReturn($existingVote);

        $this->voteRepository
            ->expects($this->never())
            ->method('save');

        $this->voteRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1, 'session123');

        $this->voteRepository
            ->expects($this->once())
            ->method('countByStatementId')
            ->with(1)
            ->willReturn(3);

        $response = $this->handler->handle($command);

        $this->assertFalse($response->voted);
        $this->assertSame(3, $response->voteCount);
    }

    public function testExplicitlyAddsVoteWhenVoteIsOne(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123',
            vote: 1
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->willReturn(null);

        $this->voteRepository
            ->expects($this->once())
            ->method('save');

        $this->voteRepository->method('countByStatementId')->willReturn(1);

        $response = $this->handler->handle($command);

        $this->assertTrue($response->voted);
    }

    public function testExplicitlyRemovesVoteWhenVoteIsZero(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123',
            vote: 0
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $existingVote = new Vote(
            id: 10,
            statementId: 1,
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->willReturn($existingVote);

        $this->voteRepository
            ->expects($this->never())
            ->method('save');

        $this->voteRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1, 'session123');

        $this->voteRepository->method('countByStatementId')->willReturn(0);

        $response = $this->handler->handle($command);

        $this->assertFalse($response->voted);
    }

    public function testDoesNothingWhenExplicitlyAddingExistingVote(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123',
            vote: 1
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $existingVote = new Vote(
            id: 10,
            statementId: 1,
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);
        $this->voteRepository->method('findByStatementAndSession')->willReturn($existingVote);

        $this->voteRepository->expects($this->never())->method('save');
        $this->voteRepository->expects($this->never())->method('delete');

        $this->voteRepository->method('countByStatementId')->willReturn(5);

        $response = $this->handler->handle($command);

        $this->assertTrue($response->voted);
    }

    public function testDoesNothingWhenExplicitlyRemovingNonExistentVote(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123',
            vote: 0
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'different-session',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);
        $this->voteRepository->method('findByStatementAndSession')->willReturn(null);

        $this->voteRepository->expects($this->never())->method('save');
        $this->voteRepository->expects($this->never())->method('delete');

        $this->voteRepository->method('countByStatementId')->willReturn(2);

        $response = $this->handler->handle($command);

        $this->assertFalse($response->voted);
    }


    public function testThrowsEntityNotFoundExceptionWhenStatementNotFound(): void
    {
        $command = new SetVoteCommand(
            statementId: 999,
            sessionId: 'session123'
        );

        $this->statementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->voteRepository->expects($this->never())->method('save');
        $this->voteRepository->expects($this->never())->method('delete');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsCannotVoteOnOwnStatementException(): void
    {
        $command = new SetVoteCommand(
            statementId: 1,
            sessionId: 'session123'
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement',
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->statementRepository->method('findById')->willReturn($statement);

        $this->voteRepository->expects($this->never())->method('save');
        $this->voteRepository->expects($this->never())->method('delete');

        $this->expectException(CannotVoteOnOwnStatementException::class);

        $this->handler->handle($command);
    }
}
