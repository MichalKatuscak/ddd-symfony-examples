<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\Event;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;

final readonly class UserActivated
{
    public function __construct(
        public UserId $userId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
