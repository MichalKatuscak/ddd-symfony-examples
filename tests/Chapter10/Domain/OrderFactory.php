<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Domain;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter02_AggregateDesign\Domain\Shipping\ShipmentId;
use App\Chapter10_Authorization\Domain\Order\Order;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

final class OrderFactory
{
    private const AT = '2026-04-29 10:00:00';

    public static function placed(
        string $at = self::AT,
        ?CustomerId $customerId = null,
    ): Order {
        return self::build($customerId ?? CustomerId::generate(), $at);
    }

    public static function placedFor(CustomerId $customerId): Order
    {
        return self::build($customerId, self::AT);
    }

    // Vlastník je parametr i zde. Bez něj by testy policy hlásily
    // porušení vlastnictví místo pravidla, které chtěly ověřit.
    public static function shipped(?CustomerId $customerId = null): Order
    {
        $order = self::build($customerId ?? CustomerId::generate(), self::AT);
        $order->markPaid();
        $order->ship(ShipmentId::generate());

        return $order;
    }

    /**
     * Builder jde přes veřejné API agregátu, ne přes reflexi. Konstruktor
     * je privátní a stav se mění jen přechody – kdyby si test sahal dovnitř,
     * přestal by hlídat právě ta pravidla, kvůli kterým existuje.
     */
    private static function build(CustomerId $customerId, string $at): Order
    {
        // Poslední parametr je čas potvrzení. Bez něj by se scénář
        // „potvrzeno v 10:00, stornováno ve 12:00“ nedal postavit jinak
        // než reflexí – a test by přestal hlídat pravidla agregátu.
        $order = Order::placeWithFirstItem(
            $customerId,
            ProductId::generate(),
            1,
            new Money(10_000, Currency::CZK),
            new \DateTimeImmutable($at),
        );

        $order->releaseEvents(); // fronta událostí patří testu, ne továrně

        return $order;
    }
}
