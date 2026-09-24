<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Cart;

interface CartRepository
{
    public function save(Cart $cart): void;

    public function getById(CartId $id): Cart;
}
