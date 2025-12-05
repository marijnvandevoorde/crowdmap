<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\CommandHandler\Statement\DeleteStatementsHandler;
use Crowdmap\Domain\Exception\CannotDeleteRootException;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeleteStatementsHandlerTest extends TestCase
{
    private MindmapRepository $mindmapRepository;
    private StatementRepository $statementRepository;
    private DeleteStatementsHandler $handler;

    protected function setUp(): void
    {
        $this->mindmapRepository = $this->createMock(MindmapRepository::class);
        $this->statementRepository = $this->createMock(StatementRepository::class);

        $this->handler = new DeleteStatementsHandler(
            $this->mindmapRepository,
            $this->statementRepository
        );
    }

    public function testSuccessfullyDeletesStatements(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [2, 3],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $statement1 = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 1',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $statement2 = new Statement(
            id: 3,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 2',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('test-uuid')
            ->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnMap([
                [2, $statement1],
                [3, $statement2],
            ]);

        $this->statementRepository
            ->expects($this->once())
            ->method('deleteWithDescendants')
            ->with([2, 3]);

        $response = $this->handler->handle($command);

        $this->assertNull($response);
    }


    public function testThrowsEntityNotFoundExceptionWhenMindmapNotFound(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'nonexistent-uuid',
            statementIds: [1],
            userId: 1
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('nonexistent-uuid')
            ->willReturn(null);

        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsUnauthorizedExceptionWhenNotOwner(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 2,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(UnauthorizedException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementNotFound(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [999],
            userId: 1
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

        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementBelongsToDifferentMindmap(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $statement = new Statement(
            id: 1,
            mindmapId: 2,
            parentId: 1,
            content: 'Statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($statement);

        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsCannotDeleteRootExceptionWhenStatementIsRoot(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $rootStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($rootStatement);

        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(CannotDeleteRootException::class);

        $this->handler->handle($command);
    }

    public function testThrowsCannotDeleteRootExceptionWhenAnyStatementIsRoot(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $rootStatement = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Root statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $childStatement = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'Child statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($rootStatement);

        $this->statementRepository->expects($this->never())->method('deleteWithDescendants');

        $this->expectException(CannotDeleteRootException::class);

        $this->handler->handle($command);
    }

    public function testDeletesSingleStatement(): void
    {
        $command = new DeleteStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [2],
            userId: 1
        );

        $mindmap = new Mindmap(
            id: 1,
            uuid: 'test-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $statement = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($statement);

        $this->statementRepository
            ->expects($this->once())
            ->method('deleteWithDescendants')
            ->with([2]);

        $response = $this->handler->handle($command);

        $this->assertNull($response);
    }
}
