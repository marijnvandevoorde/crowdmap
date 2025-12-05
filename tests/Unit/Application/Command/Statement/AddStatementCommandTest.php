<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Statement;

use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class AddStatementCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Test content',
            sessionId: 'session123'
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
        $this->assertSame(1, $command->parentId);
        $this->assertSame('Test content', $command->content);
        $this->assertSame('session123', $command->sessionId);
    }

    public function testTrimsMindmapUuid(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: '  test-uuid  ',
            parentId: 1,
            content: 'Content',
            sessionId: 'session123'
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
    }

    public function testTrimsContent(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '  Test content  ',
            sessionId: 'session123'
        );

        $this->assertSame('Test content', $command->content);
    }

    public function testTrimsSessionId(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Content',
            sessionId: '  session123  '
        );

        $this->assertSame('session123', $command->sessionId);
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new AddStatementCommand(
            mindmapUuid: '',
            parentId: 1,
            content: 'Content',
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new AddStatementCommand(
            mindmapUuid: '   ',
            parentId: 1,
            content: 'Content',
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenContentIsEmpty(): void
    {
        $this->expectException(ValidationException::class);

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '',
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenContentIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: '   ',
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenSessionIdIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID is required');

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Content',
            sessionId: ''
        );
    }

    public function testThrowsValidationExceptionWhenSessionIdIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID is required');

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 1,
            content: 'Content',
            sessionId: '   '
        );
    }

    public function testThrowsValidationExceptionWhenParentIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Parent ID must be a positive integer');

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 0,
            content: 'Content',
            sessionId: 'session123'
        );
    }

    public function testThrowsValidationExceptionWhenParentIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Parent ID must be a positive integer');

        new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: -1,
            content: 'Content',
            sessionId: 'session123'
        );
    }

    public function testAcceptsLargeParentId(): void
    {
        $command = new AddStatementCommand(
            mindmapUuid: 'test-uuid',
            parentId: 999999,
            content: 'Content',
            sessionId: 'session123'
        );

        $this->assertSame(999999, $command->parentId);
    }
}
