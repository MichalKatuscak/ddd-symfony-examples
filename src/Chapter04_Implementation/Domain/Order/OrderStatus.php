<?php

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderStatus
{
    private function __construct(private readonly string $value) {}

    public static function pending(): self { return new self('pending'); }
    public static function confirmed(): self { return new self('confirmed'); }
    public function value(): string { return $this->value; }
    public function isPending(): bool { return $this->value === 'pending'; }
}
