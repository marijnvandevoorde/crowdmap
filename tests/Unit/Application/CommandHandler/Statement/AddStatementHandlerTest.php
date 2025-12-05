<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\CommandHandler\Statement\AddStatementHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\InvalidParentStatementException;
use Crowdmap\Domain\Exception\ValidationException;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\VoteRepository;
use DateTimeImmutable;
use PDOException;
use PHPUnit\Framework\TestCase;

class AddStatementHandlerTest extends TestCase
{
    private MindmapRepository $mindmapRepository;
    private StatementRepository $statementRepository;
    private VoteRepository $voteRepository;
    private AddStatementHandler $handler;

    protected function setUp(): void
    {
        $this->mindmapRepository = $this->createMock(MindmapRepository::class);
        $this->statementRepository = $this->createMock(StatementRepository::class);
        $this->voteRepository = $this->createMock(VoteRepository::class);

        $this->handler = new AddStatementHandler(
            $this->mindmapRepository,
            $this->statementRepository,
            $this->voteRepository
        );
    }

    public function testSuccessfullyAddsNewStatement(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'New statement',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $savedStatement = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'New statement',
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('test-uuid')
            ->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($parentStatement);

        $this->statementRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn($savedStatement);

        $this->voteRepository
            ->expects($this->once())
            ->method('save');

        $response = $this->handler->handle($command);

        $this->assertSame($savedStatement, $response->statement);
        $this->assertSame(2, $response->statement->id);
        $this->assertSame('New statement', $response->statement->content);
    }

    public function testTrimsContentBeforeSaving(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '  Trimmed content  ',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        $this->statementRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Statement $statement) {
                return $statement->content === 'Trimmed content';
            }))
            ->willReturn(new Statement(
                id: 2,
                mindmapId: 1,
                parentId: 1,
                content: 'Trimmed content',
                sessionId: 'session123',
                createdAt: new DateTimeImmutable()
            ));

        $this->voteRepository->method('save');

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenContentIsEmpty(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '',
            sessionId: 'session123'
        );

        $this->mindmapRepository->expects($this->never())->method('findByUuid');

        $this->expectException(ValidationException::class);

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenContentIsOnlyWhitespace(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '   ',
            sessionId: 'session123'
        );

        $this->mindmapRepository->expects($this->never())->method('findByUuid');

        $this->expectException(ValidationException::class);

        $this->handler->handle($command);
    }

    public function testThrowsValidationExceptionWhenSessionIdIsEmpty(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Valid content',
            sessionId: ''
        );

        $this->mindmapRepository->expects($this->never())->method('findByUuid');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID is required');

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenMindmapNotFound(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'nonexistent-uuid',
            parentId: 1,
            content: 'Valid content',
            sessionId: 'session123'
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('nonexistent-uuid')
            ->willReturn(null);

        $this->statementRepository->expects($this->never())->method('save');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsInvalidParentStatementExceptionWhenParentNotFound(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 999,
            content: 'Valid content',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(InvalidParentStatementException::class);

        $this->handler->handle($command);
    }

    public function testThrowsInvalidParentStatementExceptionWhenParentBelongsToDifferentMindmap(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Valid content',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 2, // Different mindmap!
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        $this->expectException(InvalidParentStatementException::class);

        $this->handler->handle($command);
    }

    public function testHandlesDuplicateStatementByAddingVote(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Duplicate statement',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $existingStatement = new Statement(
            id: 5,
            mindmapId: 1,
            parentId: 1,
            content: 'Duplicate statement',
            sessionId: 'other-session',
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        // Simulate unique constraint violation
        $pdoException = new PDOException('UNIQUE constraint failed: idx_statements_parent_content');
        $this->statementRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException($pdoException);

        $this->statementRepository
            ->expects($this->once())
            ->method('findByParentIdAndContent')
            ->with(1, 'Duplicate statement')
            ->willReturn($existingStatement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->with(5, 'session123')
            ->willReturn(null);

        $this->voteRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Vote $vote) {
                return $vote->statementId === 5 && $vote->sessionId === 'session123';
            }));

        $response = $this->handler->handle($command);

        $this->assertSame($existingStatement, $response->statement);
        $this->assertSame(5, $response->statement->id);
    }

    public function testHandlesDuplicateStatementWhenUserAlreadyVoted(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Duplicate statement',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $existingStatement = new Statement(
            id: 5,
            mindmapId: 1,
            parentId: 1,
            content: 'Duplicate statement',
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $existingVote = new Vote(
            id: 10,
            statementId: 5,
            sessionId: 'session123',
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        $pdoException = new PDOException('UNIQUE constraint failed: idx_statements_parent_content');
        $this->statementRepository->method('save')->willThrowException($pdoException);
        $this->statementRepository->method('findByParentIdAndContent')->willReturn($existingStatement);

        $this->voteRepository
            ->expects($this->once())
            ->method('findByStatementAndSession')
            ->with(5, 'session123')
            ->willReturn($existingVote);

        // Should NOT save a new vote since user already voted
        $this->voteRepository
            ->expects($this->never())
            ->method('save');

        $response = $this->handler->handle($command);

        $this->assertSame($existingStatement, $response->statement);
    }

    public function testRethrowsPDOExceptionWhenNotUniqueConstraintViolation(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Valid content',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        // Simulate a different PDO exception
        $pdoException = new PDOException('Some other database error');
        $this->statementRepository->method('save')->willThrowException($pdoException);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Some other database error');

        $this->handler->handle($command);
    }

    public function testRethrowsPDOExceptionWhenExistingStatementNotFound(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Content',
            sessionId: 'session123'
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $parentStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($parentStatement);

        $pdoException = new PDOException('UNIQUE constraint failed: idx_statements_parent_content');
        $this->statementRepository->method('save')->willThrowException($pdoException);

        // Existing statement not found (race condition or bug)
        $this->statementRepository
            ->method('findByParentIdAndContent')
            ->willReturn(null);

        $this->expectException(PDOException::class);

        $this->handler->handle($command);
    }
}
