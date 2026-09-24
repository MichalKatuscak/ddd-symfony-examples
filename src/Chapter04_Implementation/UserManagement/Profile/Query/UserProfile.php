<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Profile\Query;

// Odpověď dotazu: hodnotové objekty ani agregát ven nepouštíme,
// šablona ani API by s nimi neuměly nic užitečného udělat.
final readonly class UserProfile
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public \DateTimeImmutable $createdAt,
    ) {}
}
