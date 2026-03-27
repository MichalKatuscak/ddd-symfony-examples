<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderStatus
{
    private function __construct(private readonly string $value) {}

    public static function pending(): self { return new self('pending'); }
    public static function confirmed(): self { return new self('confirmed'); }
    public static function fromString(string $value): self { return new self($value); }
    public function value(): string { return $this->value; }
    public function isPending(): bool { return $this->value === 'pending'; }
}
