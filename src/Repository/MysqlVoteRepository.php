<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Entity\Vote;
use DateTimeImmutable;

class MysqlVoteRepository implements VoteRepository
{
    public function __construct(
        private readonly DatabaseConnection $connection,
    ) {}

    public function save(Vote $vote): Vote
    {
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (:statement_id, :session_id, :created_at)',
            [
                'statement_id' => $vote->statementId,
                'session_id' => $vote->sessionId,
                'created_at' => $vote->createdAt->format('Y-m-d H:i:s'),
            ]
        );

        return new Vote(
            id: (int) $this->connection->lastInsertId(),
            statementId: $vote->statementId,
            sessionId: $vote->sessionId,
            createdAt: $vote->createdAt,
        );
    }

    public function delete(int $statementId, string $sessionId): bool
    {
        return $this->connection->execute(
            'DELETE FROM votes WHERE statement_id = :statement_id AND session_id = :session_id',
            [
                'statement_id' => $statementId,
                'session_id' => $sessionId,
            ]
        );
    }

    public function findByStatementAndSession(int $statementId, string $sessionId): ?Vote
    {
        $rows = $this->connection->query(
            'SELECT * FROM votes WHERE statement_id = :statement_id AND session_id = :session_id',
            [
                'statement_id' => $statementId,
                'session_id' => $sessionId,
            ]
        );

        if (empty($rows)) {
            return null;
        }

        return new Vote(
            id: (int) $rows[0]['id'],
            statementId: (int) $rows[0]['statement_id'],
            sessionId: $rows[0]['session_id'],
            createdAt: new DateTimeImmutable($rows[0]['created_at']),
        );
    }

    public function countByStatementId(int $statementId): int
    {
        $rows = $this->connection->query(
            'SELECT COUNT(*) as count FROM votes WHERE statement_id = :statement_id',
            ['statement_id' => $statementId]
        );

        return (int) $rows[0]['count'];
    }
}
