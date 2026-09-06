<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Infrastructure\Security;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;

/**
 * Infrastrukturní třída pro autentizaci. Doména o ní neví – kdyby
 * UserInterface nesl doménový agregát, svázal by model se Security
 * komponentou.
 */
final readonly class SecurityUser
{
    /** @param list<string> $roles */
    public function __construct(
        private string $email,
        private array $roles,
        private string $customerId,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    // Most do domény – Voter i handler pracují s doménovým typem.
    public function customerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }
}
