<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

final class Product
{
    public function __construct(
        private readonly ProductId $id,
        private readonly string $name,
        private readonly Price $price,
    ) {}

    public function id(): ProductId { return $this->id; }
    public function name(): string { return $this->name; }
    public function price(): Price { return $this->price; }
}
