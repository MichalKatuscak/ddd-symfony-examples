<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Registration\Command;

/**
 * PO: Command objekt jako explicitní kontrakt. Je to tentýž command
 * (stejné FQCN i pole) jako v kapitole Implementace v Symfony;
 * validační atributy výřez vynechává.
 */
final readonly class RegisterUser
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
