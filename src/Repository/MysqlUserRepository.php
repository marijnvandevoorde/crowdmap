<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Entity\User;
use DateTimeImmutable;

class MysqlUserRepository implements UserRepository
{
    public function __construct(
        private readonly DatabaseConnection $connection,
    ) {}

    public function save(User $user): User
    {
        if ($user->id === null) {
            return $this->insert($user);
        }

        return $this->update($user);
    }

    public function findById(int $id): ?User
    {
        $rows = $this->connection->query(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );

        if (empty($rows)) {
            return null;
        }

        return $this->hydrate($rows[0]);
    }

    public function findByEmail(string $email): ?User
    {
        $rows = $this->connection->query(
            'SELECT * FROM users WHERE email = :email',
            ['email' => $email]
        );

        if (empty($rows)) {
            return null;
        }

        return $this->hydrate($rows[0]);
    }

    private function insert(User $user): User
    {
        $this->connection->execute(
            'INSERT INTO users (email, name, password_hash, created_at) VALUES (:email, :name, :password_hash, :created_at)',
            [
                'email' => $user->email,
                'name' => $user->name,
                'password_hash' => $user->passwordHash,
                'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
            ]
        );

        return $user->withId((int) $this->connection->lastInsertId());
    }

    private function update(User $user): User
    {
        $now = new DateTimeImmutable();

        $this->connection->execute(
            'UPDATE users SET email = :email, name = :name, password_hash = :password_hash, updated_at = :updated_at WHERE id = :id',
            [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'password_hash' => $user->passwordHash,
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ]
        );

        return new User(
            id: $user->id,
            email: $user->email,
            name: $user->name,
            passwordHash: $user->passwordHash,
            createdAt: $user->createdAt,
            updatedAt: $now,
        );
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            email: $row['email'],
            name: $row['name'],
            passwordHash: $row['password_hash'],
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null,
        );
    }
}
