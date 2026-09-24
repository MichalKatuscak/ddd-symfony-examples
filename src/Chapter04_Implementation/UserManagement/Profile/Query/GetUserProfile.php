<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Profile\Query;

final readonly class GetUserProfile
{
    public function __construct(
        public string $userId,
    ) {}
}
