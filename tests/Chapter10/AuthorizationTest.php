<?php

declare(strict_types=1);

namespace App\Tests\Chapter10;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter10_Authorization\Application\AccessDeniedDomainException;
use App\Chapter10_Authorization\Application\CancelOrderHandler;
use App\Chapter10_Authorization\Domain\SystemActor;
use App\Chapter10_Authorization\Infrastructure\Security\OrderVoter;
use App\Chapter10_Authorization\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class AuthorizationTest extends TestCase
{
    private const OWNER = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    public function test_owner_may_cancel_own_order(): void
    {
        $order = $this->orderOf(self::OWNER);

        self::assertSame(
            OrderVoter::GRANTED,
            (new OrderVoter())->vote($this->user(self::OWNER), $order, OrderVoter::CANCEL),
        );
    }

    public function test_stranger_may_not(): void
    {
        $order = $this->orderOf(self::OWNER);

        self::assertSame(
            OrderVoter::DENIED,
            (new OrderVoter())->vote($this->user(self::STRANGER), $order, OrderVoter::CANCEL),
        );
    }

    public function test_voter_abstains_on_foreign_subject(): void
    {
        // Voter nesmí rozhodovat o něčem, co nezná.
        self::assertSame(
            OrderVoter::ABSTAIN,
            (new OrderVoter())->vote($this->user(self::OWNER), new \stdClass(), OrderVoter::CANCEL),
        );
    }

    public function test_handler_refuses_foreign_actor(): void
    {
        $order = $this->orderOf(self::OWNER);

        $this->expectException(AccessDeniedDomainException::class);
        (new CancelOrderHandler())($order, CustomerId::fromString(self::STRANGER), 'cizí storno');
    }

    public function test_system_actor_may_compensate_locked_order(): void
    {
        $order = $this->orderOf(self::OWNER);
        $order->lockForSaga();

        // Kompenzace ságy nesmí ztroskotat na zámku, který drží ona sama.
        (new CancelOrderHandler())($order, CustomerId::fromString(SystemActor::ID), 'kompenzace');

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function test_owner_cannot_cancel_while_saga_runs(): void
    {
        $order = $this->orderOf(self::OWNER);
        $order->lockForSaga();

        $this->expectException(OrderLockedBySagaException::class);
        (new CancelOrderHandler())($order, CustomerId::fromString(self::OWNER), 'teď ne');
    }

    public function test_ui_does_not_offer_cancel_on_locked_order(): void
    {
        $order = $this->orderOf(self::OWNER);
        $order->lockForSaga();

        // Tlačítko, které vede na jistou chybu, se nemá nabízet.
        self::assertFalse($order->isCancellable());
    }

    private function orderOf(string $customerId): Order
    {
        return Order::placeWithFirstItem(
            CustomerId::fromString($customerId),
            ProductId::generate(),
            1,
            new Money(30_000, Currency::CZK),
        );
    }

    private function user(string $customerId): SecurityUser
    {
        return new SecurityUser($customerId . '@example.test', ['ROLE_USER'], $customerId);
    }
}
