<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Cart;

use App\Chapter12_LesserPatterns\Ordering\Domain\Exception\CartNotFoundException;

interface CartRepository
{
    public function save(Cart $cart): void;

    /** @throws CartNotFoundException když košík neexistuje */
    public function get(CartId $id): Cart;
}
