<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Ordering;

use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    /** @return iterable<string, array{OrderStatus, OrderStatus, bool}> */
    public static function transitions(): iterable
    {
        yield 'draft → confirmed' => [OrderStatus::Draft, OrderStatus::Confirmed, true];
        yield 'draft → paid' => [OrderStatus::Draft, OrderStatus::Paid, false];
        yield 'confirmed → paid' => [OrderStatus::Confirmed, OrderStatus::Paid, true];
        yield 'paid → cancelled' => [OrderStatus::Paid, OrderStatus::Cancelled, true];
        yield 'shipped → cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled, false];
        yield 'shipped → delivered' => [OrderStatus::Shipped, OrderStatus::Delivered, true];
        yield 'delivered → cancelled' => [OrderStatus::Delivered, OrderStatus::Cancelled, false];
        yield 'cancelled → draft' => [OrderStatus::Cancelled, OrderStatus::Draft, false];
    }

    #[DataProvider('transitions')]
    public function test_transition_rules(OrderStatus $from, OrderStatus $to, bool $allowed): void
    {
        self::assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_terminal_states_have_no_transitions(): void
    {
        self::assertSame([], OrderStatus::Delivered->allowedTransitions());
        self::assertSame([], OrderStatus::Cancelled->allowedTransitions());
    }
}
