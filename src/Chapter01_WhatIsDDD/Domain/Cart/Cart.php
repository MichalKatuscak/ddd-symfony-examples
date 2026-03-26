<?php

namespace App\Chapter01_WhatIsDDD\Domain\Cart;

use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;

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
        $id = $product->id()->value;
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

    public function total(): Price
    {
        $total = null;
        foreach ($this->items as ['product' => $product, 'qty' => $qty]) {
            $lineTotal = $product->price()->multiply($qty);
            $total = $total === null ? $lineTotal : $total->add($lineTotal);
        }
        return $total ?? new Price(0, 'CZK');
    }

    /** @return array<array{name: string, qty: int, lineTotal: string}> */
    public function summary(): array
    {
        return array_values(array_map(
            fn($item) => [
                'name' => $item['product']->name(),
                'qty' => $item['qty'],
                'lineTotal' => $item['product']->price()->multiply($item['qty'])->formatted(),
            ],
            $this->items,
        ));
    }
}
