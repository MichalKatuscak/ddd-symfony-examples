<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Order;

final readonly class OrderLine
{
    public function __construct(
        public string $productName,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1');
        }
        if (empty($productName)) {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }

    /** @return array{name: string, qty: int, price: int} */
    public function toArray(): array
    {
        return [
            'name' => $this->productName,
            'qty' => $this->quantity,
            'price' => $this->unitPrice->amount,
        ];
    }

    /** @param array{name: string, qty: int, price: int} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['qty'],
            new Money($data['price'], 'CZK'),
        );
    }
}
