<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('OrderId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }
}
