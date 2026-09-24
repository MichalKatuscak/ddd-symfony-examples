<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\Event;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;

final readonly class UserRegistered
{
    public string $userId;
    public string $email;

    public function __construct(
        UserId $userId,
        Email $email,
        public \DateTimeImmutable $occurredAt,
    ) {
        // Událost nese primitivy – serializuje se bez závislosti na VO třídách.
        // Odebírá ji i jiný kontext (Identity), proto má tvar integrační události.
        $this->userId = $userId->value;
        $this->email = $email->value;
    }
}
