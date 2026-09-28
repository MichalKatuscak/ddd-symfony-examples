<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\UserManagement\Infrastructure\Repository;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter09_Migration\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\Repository\UserRepository;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;

/**
 * Fake repozitář podle kapitoly Testování DDD. Unikátnost e-mailu jen
 * aproximuje – v produkci ji vymáhá unique constraint v databázi.
 */
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $storage = [];

    public function save(User $user): void
    {
        $existing = $this->findByEmail($user->email());
        if ($existing !== null && !$existing->id->equals($user->id)) {
            throw DuplicateEmailException::with($user->email());
        }

        $this->storage[(string) $user->id] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->storage[(string) $id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->storage as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->storage);
    }
}
