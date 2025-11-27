<?php

declare(strict_types=1);

namespace Crowdmap\Repository;

use Crowdmap\Entity\User;

interface UserRepository
{
    public function save(User $user): User;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function delete(int $id): bool;
}
