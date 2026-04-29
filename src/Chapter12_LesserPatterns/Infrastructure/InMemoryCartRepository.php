<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Infrastructure;

use App\Chapter12_LesserPatterns\Domain\Order\Cart;
use App\Chapter12_LesserPatterns\Domain\Order\CartRepositoryInterface;

final class InMemoryCartRepository implements CartRepositoryInterface
{
    /** @var array<string, Cart> */
    private array $carts = [];

    public function save(Cart $cart): void
    {
        $this->carts[$cart->id()] = $cart;
    }

    public function findById(string $id): Cart
    {
        return $this->carts[$id]
            ?? throw new \RuntimeException(sprintf('Cart "%s" not found.', $id));
    }
}
