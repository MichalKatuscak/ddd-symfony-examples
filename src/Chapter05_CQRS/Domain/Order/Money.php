<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Domain\Order;
final readonly class Money
{
    public function __construct(public int $amount, public string $currency) {}
    public function add(self $other): self { return new self($this->amount + $other->amount, $this->currency); }
    public function multiply(int $qty): self { return new self($this->amount * $qty, $this->currency); }
    public function formatted(): string { return number_format($this->amount / 100, 2) . ' ' . $this->currency; }
}
