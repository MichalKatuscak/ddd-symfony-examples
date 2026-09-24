<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class OrderIdTest extends TestCase
{
    public function test_generate_returns_uuid_v7(): void
    {
        $id = OrderId::generate();

        self::assertTrue(Uuid::isValid($id->value));
        self::assertInstanceOf(UuidV7::class, Uuid::fromString($id->value));
    }

    public function test_generate_returns_unique_values(): void
    {
        self::assertFalse(OrderId::generate()->equals(OrderId::generate()));
    }

    public function test_non_uuid_value_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderId('some-id-123');
    }

    public function test_from_string_and_to_string_round_trip(): void
    {
        $value = OrderId::generate()->value;
        $id = OrderId::fromString($value);

        self::assertSame($value, (string) $id);
        self::assertTrue($id->equals(new OrderId($value)));
    }

    public function test_customer_id_has_the_same_shape(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CustomerId::fromString('zákazník-1');
    }
}
