<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Statement\MergeStatementsCommand;
use Crowdmap\Application\CommandHandler\Statement\MergeStatementsHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\StatementsNotSiblingsException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MergeStatementsHandlerTest extends TestCase
{
    private MindmapRepository $mindmapRepository;
    private StatementRepository $statementRepository;
    private MergeStatementsHandler $handler;

    protected function setUp(): void
    {
        $this->mindmapRepository = $this->createMock(MindmapRepository::class);
        $this->statementRepository = $this->createMock(StatementRepository::class);

        $this->handler = new MergeStatementsHandler(
            $this->mindmapRepository,
            $this->statementRepository
        );
    }

    public function testSuccessfullyMergesStatements(): void
    {
        $command = new MergeStatementsCommand(
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
            sessionId: 'session1',
            createdAt: new DateTimeImmutable()
        );

        $statement2 = new Statement(
            id: 3,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 2',
            sessionId: 'session2',
            createdAt: new DateTimeImmutable()
        );

        $mergedStatement = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 1',
            sessionId: 'session1',
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
            ->method('merge')
            ->with([2, 3])
            ->willReturn($mergedStatement);

        $response = $this->handler->handle($command);

        $this->assertSame($mergedStatement, $response->mergedStatement);
    }


    public function testThrowsEntityNotFoundExceptionWhenMindmapNotFound(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'nonexistent-uuid',
            statementIds: [1, 2],
            userId: 1
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('nonexistent-uuid')
            ->willReturn(null);

        $this->statementRepository->expects($this->never())->method('merge');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsUnauthorizedExceptionWhenNotOwner(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2],
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
        $this->statementRepository->expects($this->never())->method('merge');

        $this->expectException(UnauthorizedException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementNotFound(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 999],
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
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement 1',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnMap([
                [1, $statement1],
                [999, null],
            ]);

        $this->statementRepository->expects($this->never())->method('merge');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementBelongsToDifferentMindmap(): void
    {
        $command = new MergeStatementsCommand(
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

        $statement1 = new Statement(
            id: 1,
            mindmapId: 1,
            parentId: null,
            content: 'Statement 1',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $statement2 = new Statement(
            id: 2,
            mindmapId: 2,
            parentId: null,
            content: 'Statement 2',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnMap([
                [1, $statement1],
                [2, $statement2],
            ]);

        $this->statementRepository->expects($this->never())->method('merge');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsStatementsNotSiblingsExceptionWhenDifferentParents(): void
    {
        $command = new MergeStatementsCommand(
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
            parentId: 5,
            content: 'Statement 2',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnMap([
                [2, $statement1],
                [3, $statement2],
            ]);

        $this->statementRepository->expects($this->never())->method('merge');

        $this->expectException(StatementsNotSiblingsException::class);

        $this->handler->handle($command);
    }

    public function testMergesThreeStatements(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [2, 3, 4],
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

        $statement3 = new Statement(
            id: 4,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 3',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $mergedStatement = new Statement(
            id: 2,
            mindmapId: 1,
            parentId: 1,
            content: 'Statement 1',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);

        $this->statementRepository
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturnMap([
                [2, $statement1],
                [3, $statement2],
                [4, $statement3],
            ]);

        $this->statementRepository
            ->expects($this->once())
            ->method('merge')
            ->with([2, 3, 4])
            ->willReturn($mergedStatement);

        $response = $this->handler->handle($command);

        $this->assertSame($mergedStatement, $response->mergedStatement);
    }
}
