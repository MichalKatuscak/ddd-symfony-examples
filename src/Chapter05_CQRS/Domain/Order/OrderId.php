<?php
namespace App\Chapter05_CQRS\Domain\Order;
final readonly class OrderId
{
    public function __construct(public readonly string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('OrderId cannot be empty');
    }
    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
