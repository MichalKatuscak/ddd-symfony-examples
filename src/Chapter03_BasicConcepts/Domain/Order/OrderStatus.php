<?php

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderStatus
{
    private function __construct(private readonly string $value) {}

    public static function pending(): self { return new self('pending'); }
    public static function confirmed(): self { return new self('confirmed'); }
    public static function cancelled(): self { return new self('cancelled'); }

    public function isPending(): bool { return $this->value === 'pending'; }
    public function isConfirmed(): bool { return $this->value === 'confirmed'; }
    public function value(): string { return $this->value; }
}
