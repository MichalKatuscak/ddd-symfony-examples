<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\Event;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;

/**
 * Na rozdíl od událostí objednávky nese primitivy: odebírá ji i kontext
 * Identity, takže má tvar integrační události. Posluchač z ní poskládá
 * reakci, aniž by sahal zpátky do UserRepository.
 */
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
        $this->userId = $userId->value;
        $this->email = $email->value;
    }
}
