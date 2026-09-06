<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public function test_has_draft_case(): void
    {
        $this->assertSame('draft', OrderStatus::Draft->value);
    }

    public function test_has_confirmed_case(): void
    {
        $this->assertSame('confirmed', OrderStatus::Confirmed->value);
    }

    public function test_has_cancelled_case(): void
    {
        $this->assertSame('cancelled', OrderStatus::Cancelled->value);
    }

    public function test_from_draft_string_works(): void
    {
        $status = OrderStatus::from('draft');
        $this->assertSame(OrderStatus::Draft, $status);
    }

    public function test_from_confirmed_string_works(): void
    {
        $status = OrderStatus::from('confirmed');
        $this->assertSame(OrderStatus::Confirmed, $status);
    }

    public function test_from_cancelled_string_works(): void
    {
        $status = OrderStatus::from('cancelled');
        $this->assertSame(OrderStatus::Cancelled, $status);
    }
}
