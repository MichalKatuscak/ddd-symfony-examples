<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Customer;

final class Customer
{
    public function __construct(
        private readonly CustomerId $id,
        private readonly string $email,
        private readonly string $countryCode,
        private bool $premium = false,
    ) {}

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function countryCode(): string
    {
        return strtoupper($this->countryCode);
    }

    public function isPremium(): bool
    {
        return $this->premium;
    }

    public function promoteToPremium(): void
    {
        $this->premium = true;
    }
}
