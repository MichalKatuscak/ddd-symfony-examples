<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Cart;

use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/** Doménová logika košíku bez frameworku a databáze. */
final class Cart
{
    /** @var array<string, array{product: Product, qty: int}> */
    private array $items = [];

    private function __construct() {}

    public static function empty(): self
    {
        return new self();
    }

    public function add(Product $product, int $qty): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }
        $id = $product->id->value;
        if (isset($this->items[$id])) {
            $this->items[$id]['qty'] += $qty;
        } else {
            $this->items[$id] = ['product' => $product, 'qty' => $qty];
        }
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->items, 'qty'));
    }

    // Prázdný košík má nulovou hodnotu – na rozdíl od objednávky, která
    // bez položek součet nemá (viz Chapter03_BasicConcepts).
    public function totalAmount(): Money
    {
        $total = Money::zero(Currency::CZK);
        foreach ($this->items as ['product' => $product, 'qty' => $qty]) {
            $total = $total->add($product->price()->multiply($qty));
        }

        return $total;
    }

    /** @return list<array{name: string, qty: int, lineTotal: Money}> */
    public function summary(): array
    {
        return array_values(array_map(
            static fn (array $item): array => [
                'name' => $item['product']->name(),
                'qty' => $item['qty'],
                'lineTotal' => $item['product']->price()->multiply($item['qty']),
            ],
            $this->items,
        ));
    }
}
