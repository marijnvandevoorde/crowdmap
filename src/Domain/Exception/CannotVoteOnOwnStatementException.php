<?php

declare(strict_types=1);

namespace Crowdmap\Domain\Exception;

/**
 * Exception thrown when a user attempts to vote on their own statement.
 */
final class CannotVoteOnOwnStatementException extends DomainException
{
    public static function create(): self
    {
        return new self('Cannot vote on own statement');
    }
}
