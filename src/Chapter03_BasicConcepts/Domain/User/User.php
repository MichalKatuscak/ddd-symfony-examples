<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\User;

/**
 * Entita: identitu určuje UserId, jméno i e-mail se smí měnit.
 * Podoba bez perzistence ze sekce 06.03.
 */
class User
{
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        // Identita je veřejná readonly vlastnost, stejně jako v kanonickém
        // User z kapitoly Implementace v Symfony.
        public readonly UserId $id,
        private string $name,
        private Email $email,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function changeName(string $name): void
    {
        $this->name = $name;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    // Rovnost entit stojí jen na identitě: == srovnává všechny vlastnosti,
    // === instance v paměti. Ani jedno neodpovídá doménové totožnosti.
    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
