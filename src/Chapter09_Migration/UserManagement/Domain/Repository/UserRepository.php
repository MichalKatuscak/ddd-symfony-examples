<?php

declare(strict_types=1);

// Doménové rozhraní – součást domény, žádná infrastrukturní závislost
namespace App\Chapter09_Migration\UserManagement\Domain\Repository;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter09_Migration\UserManagement\Domain\Model\User;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    /** @return User[] */
    public function findActiveUsers(): array;
}
