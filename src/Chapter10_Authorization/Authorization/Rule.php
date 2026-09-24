<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Authorization;

final readonly class Rule
{
    public function __construct(
        public string $expression,
        public string $description,
    ) {}
}
