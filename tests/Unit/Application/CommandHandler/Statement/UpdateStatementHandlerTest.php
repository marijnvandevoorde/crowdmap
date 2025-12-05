<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Statement;

use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\CommandHandler\Statement\UpdateStatementHandler;
use Crowdmap\Domain\Exception\EntityNotFoundException;
use Crowdmap\Domain\Exception\UnauthorizedException;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UpdateStatementHandlerTest extends TestCase
{
    private MindmapRepository $mindmapRepository;
    private StatementRepository $statementRepository;
    private UpdateStatementHandler $handler;

    protected function setUp(): void
    {
        $this->mindmapRepository = $this->createMock(MindmapRepository::class);
        $this->statementRepository = $this->createMock(StatementRepository::class);

        $this->handler = new UpdateStatementHandler(
            $this->mindmapRepository,
            $this->statementRepository
        );
    }

    public function testSuccessfullyUpdatesStatement(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 2,
            content: 'Updated content',
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
            content: 'Original content',
            sessionId: null,
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
            ->with(2)
            ->willReturn($statement);

        $this->statementRepository
            ->expects($this->once())
            ->method('updateContent')
            ->with(2, 'Updated content');

        $response = $this->handler->handle($command);

        $this->assertNull($response);
    }


    public function testThrowsEntityNotFoundExceptionWhenMindmapNotFound(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'nonexistent-uuid',
            statementId: 2,
            content: 'Valid content',
            userId: 1
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('nonexistent-uuid')
            ->willReturn(null);

        $this->statementRepository->expects($this->never())->method('updateContent');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsUnauthorizedExceptionWhenNotOwner(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 2,
            content: 'Valid content',
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
        $this->statementRepository->expects($this->never())->method('updateContent');

        $this->expectException(UnauthorizedException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementNotFound(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 999,
            content: 'Valid content',
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

        $this->statementRepository->expects($this->never())->method('updateContent');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testThrowsEntityNotFoundExceptionWhenStatementBelongsToDifferentMindmap(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 2,
            content: 'Valid content',
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
            mindmapId: 2,
            parentId: 1,
            content: 'Original',
            sessionId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('findByUuid')->willReturn($mindmap);
        $this->statementRepository->method('findById')->willReturn($statement);

        $this->statementRepository->expects($this->never())->method('updateContent');

        $this->expectException(EntityNotFoundException::class);

        $this->handler->handle($command);
    }
}
