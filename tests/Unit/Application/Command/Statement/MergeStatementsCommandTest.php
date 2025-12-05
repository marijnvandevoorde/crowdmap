<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Statement;

use Crowdmap\Application\Command\Statement\MergeStatementsCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class MergeStatementsCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2],
            userId: 1
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
        $this->assertSame([1, 2], $command->statementIds);
        $this->assertSame(1, $command->userId);
    }

    public function testTrimsMindmapUuid(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: '  test-uuid  ',
            statementIds: [1, 2],
            userId: 1
        );

        $this->assertSame('test-uuid', $command->mindmapUuid);
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new MergeStatementsCommand(
            mindmapUuid: '',
            statementIds: [1, 2],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenMindmapUuidIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mindmap UUID is required');

        new MergeStatementsCommand(
            mindmapUuid: '   ',
            statementIds: [1, 2],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdsIsEmpty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('At least 2 statement IDs required');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenOnlyOneStatementId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('At least 2 statement IDs required');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All statement IDs must be positive integers');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 0],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All statement IDs must be positive integers');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, -1],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenStatementIdIsNotInteger(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All statement IDs must be positive integers');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, '2'],
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2],
            userId: 0
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2],
            userId: -1
        );
    }

    public function testAcceptsExactlyTwoStatementIds(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [5, 10],
            userId: 1
        );

        $this->assertSame([5, 10], $command->statementIds);
    }

    public function testAcceptsMultipleStatementIds(): void
    {
        $command = new MergeStatementsCommand(
            mindmapUuid: 'test-uuid',
            statementIds: [1, 2, 3, 4, 5],
            userId: 1
        );

        $this->assertSame([1, 2, 3, 4, 5], $command->statementIds);
    }
}
