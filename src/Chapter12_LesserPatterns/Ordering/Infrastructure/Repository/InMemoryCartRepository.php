<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Infrastructure\Repository;

use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\Cart;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartId;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: CartRepository::class)]
final class InMemoryCartRepository implements CartRepository
{
    /** @var array<string, Cart> */
    private array $carts = [];

    public function save(Cart $cart): void
    {
        $this->carts[$cart->id->value] = $cart;
    }

    public function getById(CartId $id): Cart
    {
        return $this->carts[$id->value]
            ?? throw new \OutOfBoundsException(sprintf('Košík „%s“ neexistuje.', $id->value));
    }
}
