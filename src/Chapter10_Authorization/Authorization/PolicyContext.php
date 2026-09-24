<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Authorization;

final readonly class PolicyContext
{
    public function __construct(
        public object $subject,
        public object $user,
        public \DateTimeImmutable $now,
    ) {}
}
