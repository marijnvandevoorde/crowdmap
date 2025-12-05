<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Application\Command\Mindmap;

use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class CreateMindmapCommandTest extends TestCase
{
    public function testCreatesCommandWithValidInputs(): void
    {
        $command = new CreateMindmapCommand(
            question: 'What is the meaning of life?',
            userId: 1
        );

        $this->assertSame('What is the meaning of life?', $command->question);
        $this->assertSame(1, $command->userId);
    }

    public function testTrimsQuestion(): void
    {
        $command = new CreateMindmapCommand(
            question: '  What is the answer?  ',
            userId: 1
        );

        $this->assertSame('What is the answer?', $command->question);
    }

    public function testThrowsValidationExceptionWhenQuestionIsEmpty(): void
    {
        $this->expectException(ValidationException::class);

        new CreateMindmapCommand(
            question: '',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenQuestionIsWhitespace(): void
    {
        $this->expectException(ValidationException::class);

        new CreateMindmapCommand(
            question: '   ',
            userId: 1
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsZero(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new CreateMindmapCommand(
            question: 'Valid question',
            userId: 0
        );
    }

    public function testThrowsValidationExceptionWhenUserIdIsNegative(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new CreateMindmapCommand(
            question: 'Valid question',
            userId: -1
        );
    }
}
