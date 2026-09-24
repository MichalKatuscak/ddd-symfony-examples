<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Identity;

use App\Chapter10_Authorization\Infrastructure\Security\SecurityUser;

final class SecurityUserFixture
{
    /** Aktér pro test Voteru. Zajímá ho jen customerId, zbytek je výplň. */
    public static function for(string $customerId, string ...$roles): SecurityUser
    {
        // E-mail je v knize primární klíč, takže musí být pro každého aktéra jiný.
        return new SecurityUser(
            email: $customerId . '@example.test',
            passwordHash: 'irrelevant',
            roles: $roles ?: ['ROLE_USER'],
            customerId: $customerId,
            tenantId: 'tenant-test',
        );
    }
}
