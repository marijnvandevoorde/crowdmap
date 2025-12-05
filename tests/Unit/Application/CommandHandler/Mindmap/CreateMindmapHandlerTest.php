<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\CommandHandler\Mindmap;

use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\CommandHandler\Mindmap\CreateMindmapHandler;
use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\StatementRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class CreateMindmapHandlerTest extends TestCase
{
    private MindmapRepository $mindmapRepository;
    private StatementRepository $statementRepository;
    private CreateMindmapHandler $handler;

    protected function setUp(): void
    {
        $this->mindmapRepository = $this->createMock(MindmapRepository::class);
        $this->statementRepository = $this->createMock(StatementRepository::class);

        $this->handler = new CreateMindmapHandler(
            $this->mindmapRepository,
            $this->statementRepository
        );
    }

    public function testSuccessfullyCreatesMindmap(): void
    {
        $command = new CreateMindmapCommand(
            question: 'What is the meaning of life?',
            userId: 1
        );

        $savedMindmap = new Mindmap(
            id: 1,
            uuid: 'generated-uuid',
            userId: 1,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Mindmap $mindmap) {
                return $mindmap->userId === 1
                    && $mindmap->uuid !== null
                    && strlen($mindmap->uuid) > 0;
            }))
            ->willReturn($savedMindmap);

        $this->statementRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Statement $statement) {
                return $statement->mindmapId === 1
                    && $statement->parentId === null
                    && $statement->content === 'What is the meaning of life?';
            }));

        $response = $this->handler->handle($command);

        $this->assertSame($savedMindmap, $response->mindmap);
        $this->assertSame(1, $response->mindmap->id);
        $this->assertSame('generated-uuid', $response->mindmap->uuid);
    }


    public function testCreatesRootStatementWithCorrectProperties(): void
    {
        $command = new CreateMindmapCommand(
            question: 'Test question',
            userId: 42
        );

        $savedMindmap = new Mindmap(
            id: 10,
            uuid: 'test-uuid',
            userId: 42,
            focusedStatementId: null,
            createdAt: new DateTimeImmutable()
        );

        $this->mindmapRepository->method('save')->willReturn($savedMindmap);

        $this->statementRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Statement $statement) {
                return $statement->mindmapId === 10
                    && $statement->parentId === null
                    && $statement->content === 'Test question'
                    && $statement->sessionId === null;
            }));

        $this->handler->handle($command);
    }
}
