<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Infrastructure\Security;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter10_Authorization\Domain\TenantId;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Most mezi Symfony Security a doménou. Doménový agregát o něm neví –
 * kdyby UserInterface nesl doménový User, svázal by model se Security
 * komponentou.
 *
 * Kniha třídu mapuje jako Doctrine entitu na tabulku `app_user`.
 * Ukázka nic neukládá, a tak mapování nemá.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @param list<string> $roles */
    public function __construct(
        private string $email,
        private string $passwordHash,
        private array $roles,
        private string $customerId,
        private string $tenantId,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    // Most do domény – Voter i handler pracují s doménovým typem
    public function customerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function tenantId(): TenantId
    {
        return TenantId::fromString($this->tenantId);
    }
}
