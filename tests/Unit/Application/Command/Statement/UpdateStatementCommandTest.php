<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Statement;

use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class UpdateStatementCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 5,
            content: 'Updated content',
            userId: 1
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
        $this->assertSame(5, $command->statementId);
        $this->assertSame('Updated content', $command->content);
        $this->assertSame(1, $command->userId);
    }

    public function testTrimsMindmapUuid(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: '  test-uuid  ',
            statementId: 1,
            content: 'Content',
            userId: 1
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
    }

    public function testTrimsContent(): void
    {
        $command = new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 1,
            content: '  Updated content  ',
            userId: 1
        );

        $this->assertSame('Updated content', $command->content);
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new UpdateStatementCommand(
            mindmapUuid: '',
            statementId: 1,
            content: 'Content',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new UpdateStatementCommand(
            mindmapUuid: '   ',
            statementId: 1,
            content: 'Content',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Statement ID must be a positive integer');

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 0,
            content: 'Content',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Statement ID must be a positive integer');

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: -1,
            content: 'Content',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenContentIsEmpty(): void
    {
        $this->expectException(ValidationException::class);

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 1,
            content: '',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenContentIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 1,
            content: '   ',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 1,
            content: 'Content',
            userId: 0
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new UpdateStatementCommand(
            mindmapUuid: 'test-uuid',
            statementId: 1,
            content: 'Content',
            userId: -1
        );
    }
}
