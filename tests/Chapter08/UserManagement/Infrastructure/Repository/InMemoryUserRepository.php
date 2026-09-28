<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\UserManagement\Infrastructure\Repository;

use App\Chapter04_Implementation\UserManagement\Domain\Exception\DuplicateEmailException;
use App\Chapter04_Implementation\UserManagement\Domain\Model\User;
use App\Chapter04_Implementation\UserManagement\Domain\Repository\UserRepository;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;

/**
 * InMemory implementace UserRepository pro unit a integrační testy.
 * Simuluje chování Doctrine repozitáře bez potřeby databáze.
 */
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $storage = [];

    public function save(User $user): void
    {
        // Aproximace unikátního indexu na sloupci `email`. V produkci ho
        // vymáhá databáze a handler překládá UniqueConstraintViolationException;
        // fake ho vymáhá sám, aby test nepotřeboval běžící DB.
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

    public function remove(User $user): void
    {
        unset($this->storage[(string) $user->id]);
    }

    /** Pomocná metoda pro aserce v testech. */
    public function count(): int
    {
        return count($this->storage);
    }

    /** @return array<User> */
    public function all(): array
    {
        return array_values($this->storage);
    }
}
