<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

// Ilustrační výřez: agregát Customer kniha dál nerozvádí, službě stačí
// věrnostní status. Skutečný model by ho odvozoval z historie nákupů.
final class Customer
{
    public function __construct(
        public readonly CustomerId $id,
        private bool $vip = false,
    ) {}

    public function isVip(): bool
    {
        return $this->vip;
    }
}
