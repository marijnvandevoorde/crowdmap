<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Integration\Repository;

use Crowdmap\Entity\Mindmap;
use Crowdmap\Entity\Statement;
use Crowdmap\Entity\Vote;
use Crowdmap\Repository\SqliteStatementRepository;
use Crowdmap\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use PDOException;

class StatementRepositoryTest extends DatabaseTestCase
{
    private SqliteStatementRepository $repository;
    private int $mindmapId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new SqliteStatementRepository($this->connection);

        // Create a test mindmap
        $this->connection->execute(
            'INSERT INTO users (email, name, password_hash, created_at) VALUES (?, ?, ?, ?)',
            ['test@example.com', 'Test User', 'hashed', '2024-01-01 00:00:00']
        );
        $userId = (int) $this->connection->lastInsertId();

        $this->connection->execute(
            'INSERT INTO mindmaps (uuid, user_id, created_at) VALUES (?, ?, ?)',
            ['test-uuid', $userId, '2024-01-01 00:00:00']
        );
        $this->mindmapId = (int) $this->connection->lastInsertId();
    }

    public function testSaveStatementInsertsAndReturnsStatementWithId(): void
    {
        $statement = Statement::createRoot($this->mindmapId, 'Root question');

        $saved = $this->repository->save($statement);

        $this->assertNotNull($saved->id);
        $this->assertGreaterThan(0, $saved->id);
        $this->assertSame($this->mindmapId, $saved->mindmapId);
        $this->assertNull($saved->parentId);
        $this->assertSame('Root question', $saved->content);
        $this->assertNull($saved->sessionId);
    }

    public function testSaveStatementWithParent(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));

        $child = Statement::createReaction($this->mindmapId, $root->id, 'Child statement', 'session123');
        $saved = $this->repository->save($child);

        $this->assertNotNull($saved->id);
        $this->assertSame($root->id, $saved->parentId);
        $this->assertSame('Child statement', $saved->content);
        $this->assertSame('session123', $saved->sessionId);
    }

    public function testFindByIdReturnsStatementWhenExists(): void
    {
        $statement = $this->repository->save(Statement::createRoot($this->mindmapId, 'Test'));

        $found = $this->repository->findById($statement->id);

        $this->assertNotNull($found);
        $this->assertSame($statement->id, $found->id);
        $this->assertSame('Test', $found->content);
    }

    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    public function testFindRootByMindmapIdReturnsRootStatement(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root question'));
        $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child', 'session1'));

        $found = $this->repository->findRootByMindmapId($this->mindmapId);

        $this->assertNotNull($found);
        $this->assertSame($root->id, $found->id);
        $this->assertNull($found->parentId);
        $this->assertSame('Root question', $found->content);
    }

    public function testFindRootByMindmapIdReturnsNullWhenNoRoot(): void
    {
        $found = $this->repository->findRootByMindmapId(999);

        $this->assertNull($found);
    }

    public function testFindByParentIdReturnsChildStatements(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 1', 'session1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 2', 'session2'));

        $children = $this->repository->findByParentId($root->id);

        $this->assertCount(2, $children);
        $this->assertSame('Child 1', $children[0]->content);
        $this->assertSame('Child 2', $children[1]->content);
    }

    public function testFindByParentIdReturnsEmptyArrayWhenNoChildren(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));

        $children = $this->repository->findByParentId($root->id);

        $this->assertEmpty($children);
    }

    public function testFindByParentIdAndContentReturnsMatchingStatement(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Specific content', 'session1'));

        $found = $this->repository->findByParentIdAndContent($root->id, 'Specific content');

        $this->assertNotNull($found);
        $this->assertSame($child->id, $found->id);
    }

    public function testFindByParentIdAndContentReturnsNullWhenNoMatch(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));

        $found = $this->repository->findByParentIdAndContent($root->id, 'Non-existent content');

        $this->assertNull($found);
    }

    public function testFindByMindmapIdWithVoteCountsReturnsStatementsWithCounts(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 1', 'session1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 2', 'session2'));

        // Add votes
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$child1->id, 'voter1', '2024-01-01 00:00:00']
        );
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$child1->id, 'voter2', '2024-01-01 00:00:00']
        );
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$child2->id, 'voter1', '2024-01-01 00:00:00']
        );

        $results = $this->repository->findByMindmapIdWithVoteCounts($this->mindmapId);

        $this->assertCount(3, $results);

        // Root has 0 votes
        $this->assertSame($root->id, $results[0]['statement']->id);
        $this->assertSame(0, $results[0]['vote_count']);

        // Child 1 has 2 votes
        $this->assertSame($child1->id, $results[1]['statement']->id);
        $this->assertSame(2, $results[1]['vote_count']);

        // Child 2 has 1 vote
        $this->assertSame($child2->id, $results[2]['statement']->id);
        $this->assertSame(1, $results[2]['vote_count']);
    }

    public function testUpdateContentModifiesStatementContent(): void
    {
        $statement = $this->repository->save(Statement::createRoot($this->mindmapId, 'Original content'));

        $this->repository->updateContent($statement->id, 'Updated content');

        $updated = $this->repository->findById($statement->id);
        $this->assertSame('Updated content', $updated->content);
    }

    public function testDeleteRemovesStatement(): void
    {
        $statement = $this->repository->save(Statement::createRoot($this->mindmapId, 'To delete'));

        $this->repository->delete($statement->id);

        $found = $this->repository->findById($statement->id);
        $this->assertNull($found);
    }

    public function testDeleteAlsoRemovesVotes(): void
    {
        $statement = $this->repository->save(Statement::createRoot($this->mindmapId, 'Statement'));
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$statement->id, 'voter1', '2024-01-01 00:00:00']
        );

        $this->repository->delete($statement->id);

        $votes = $this->connection->query(
            'SELECT * FROM votes WHERE statement_id = ?',
            [$statement->id]
        );
        $this->assertEmpty($votes);
    }

    public function testDeleteWithDescendantsRemovesStatementAndChildren(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 1', 's1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 2', 's2'));
        $grandchild = $this->repository->save(Statement::createReaction($this->mindmapId, $child1->id, 'Grandchild', 's3'));

        $this->repository->deleteWithDescendants([$child1->id]);

        $this->assertNull($this->repository->findById($child1->id));
        $this->assertNull($this->repository->findById($grandchild->id));
        $this->assertNotNull($this->repository->findById($root->id));
        $this->assertNotNull($this->repository->findById($child2->id));
    }

    public function testDeleteWithDescendantsRemovesMultipleStatements(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 1', 's1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 2', 's2'));

        $this->repository->deleteWithDescendants([$child1->id, $child2->id]);

        $this->assertNull($this->repository->findById($child1->id));
        $this->assertNull($this->repository->findById($child2->id));
        $this->assertNotNull($this->repository->findById($root->id));
    }

    public function testDeleteWithDescendantsHandlesDeepNesting(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $level1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Level 1', 's1'));
        $level2 = $this->repository->save(Statement::createReaction($this->mindmapId, $level1->id, 'Level 2', 's2'));
        $level3 = $this->repository->save(Statement::createReaction($this->mindmapId, $level2->id, 'Level 3', 's3'));

        $this->repository->deleteWithDescendants([$level1->id]);

        $this->assertNull($this->repository->findById($level1->id));
        $this->assertNull($this->repository->findById($level2->id));
        $this->assertNull($this->repository->findById($level3->id));
        $this->assertNotNull($this->repository->findById($root->id));
    }

    public function testMergeMergesTwoStatements(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'First', 's1'));
        $stmt2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Second', 's2'));

        $merged = $this->repository->merge([$stmt1->id, $stmt2->id]);

        $this->assertSame($stmt1->id, $merged->id);
        $this->assertStringContainsString('First', $merged->content);
        $this->assertStringContainsString('Second', $merged->content);
        $this->assertNull($this->repository->findById($stmt2->id));
    }

    public function testMergeTransfersChildrenToTargetStatement(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'First', 's1'));
        $stmt2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Second', 's2'));

        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $stmt1->id, 'Child of 1', 's3'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $stmt2->id, 'Child of 2', 's4'));

        $merged = $this->repository->merge([$stmt1->id, $stmt2->id]);

        $reloadedChild1 = $this->repository->findById($child1->id);
        $reloadedChild2 = $this->repository->findById($child2->id);

        $this->assertSame($merged->id, $reloadedChild1->parentId);
        $this->assertSame($merged->id, $reloadedChild2->parentId);
    }

    public function testMergeTransfersVotesToTargetStatement(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'First', 's1'));
        $stmt2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Second', 's2'));

        // Add votes to both statements
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$stmt1->id, 'voter1', '2024-01-01 00:00:00']
        );
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$stmt2->id, 'voter2', '2024-01-01 00:00:00']
        );
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$stmt2->id, 'voter3', '2024-01-01 00:00:00']
        );

        $merged = $this->repository->merge([$stmt1->id, $stmt2->id]);

        $votes = $this->connection->query(
            'SELECT * FROM votes WHERE statement_id = ?',
            [$merged->id]
        );

        $this->assertCount(3, $votes);
    }

    public function testMergeDoesNotDuplicateVotesFromSameSession(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'First', 's1'));
        $stmt2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Second', 's2'));

        // Add vote from same session to both statements
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$stmt1->id, 'same-voter', '2024-01-01 00:00:00']
        );
        $this->connection->execute(
            'INSERT INTO votes (statement_id, session_id, created_at) VALUES (?, ?, ?)',
            [$stmt2->id, 'same-voter', '2024-01-01 00:00:00']
        );

        $merged = $this->repository->merge([$stmt1->id, $stmt2->id]);

        $votes = $this->connection->query(
            'SELECT * FROM votes WHERE statement_id = ?',
            [$merged->id]
        );

        // Should only have 1 vote from 'same-voter', not 2
        $this->assertCount(1, $votes);
    }

    public function testMergeThrowsExceptionWhenLessThanTwoStatements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 2 statements required');

        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $this->repository->merge([$root->id]);
    }

    public function testMergeThrowsExceptionWhenStatementNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statement 999 not found');

        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Valid', 's1'));

        $this->repository->merge([$stmt->id, 999]);
    }

    public function testMergeThrowsExceptionWhenStatementsHaveDifferentParents(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All statements must have the same parent');

        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 1', 's1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Child 2', 's2'));

        $grandchild = $this->repository->save(Statement::createReaction($this->mindmapId, $child1->id, 'Grandchild', 's3'));

        // Try to merge child2 with grandchild (different parents)
        $this->repository->merge([$child2->id, $grandchild->id]);
    }

    public function testUniqueConstraintOnParentIdAndContent(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Same content', 's1'));

        $this->expectException(PDOException::class);

        // Try to insert duplicate with same parent and content
        $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Same content', 's2'));
    }

    public function testDifferentContentsWithSameParentAreAllowed(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $stmt1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Content A', 's1'));
        $stmt2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Content B', 's2'));

        $this->assertNotSame($stmt1->id, $stmt2->id);
    }

    public function testSameContentWithDifferentParentsIsAllowed(): void
    {
        $root = $this->repository->save(Statement::createRoot($this->mindmapId, 'Root'));
        $child1 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Parent 1', 's1'));
        $child2 = $this->repository->save(Statement::createReaction($this->mindmapId, $root->id, 'Parent 2', 's2'));

        $grandchild1 = $this->repository->save(Statement::createReaction($this->mindmapId, $child1->id, 'Same content', 's3'));
        $grandchild2 = $this->repository->save(Statement::createReaction($this->mindmapId, $child2->id, 'Same content', 's4'));

        $this->assertNotSame($grandchild1->id, $grandchild2->id);
    }
}
