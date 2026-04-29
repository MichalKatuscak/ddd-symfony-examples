<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

interface CartRepositoryInterface
{
    public function save(Cart $cart): void;

    public function findById(string $id): Cart;
}
