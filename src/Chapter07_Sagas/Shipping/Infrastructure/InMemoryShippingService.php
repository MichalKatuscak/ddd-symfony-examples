<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Infrastructure;

use App\Chapter07_Sagas\Shipping\Domain\ShippingService;
use Symfony\Component\Uid\Uuid;

final class InMemoryShippingService implements ShippingService
{
    /** @var array<string, true> */
    private array $cancelled = [];

    public function create(string $orderId): string
    {
        // Rozhraní vrací řetězec, ne hodnotový objekt: identifikátor
        // putuje v události přes hranici kontextu. ShipmentId z něj
        // sestaví až ShipOrderHandler v Orderingu.
        return (string) Uuid::v7();
    }

    public function cancel(string $shipmentId): void
    {
        $this->cancelled[$shipmentId] = true;
    }

    public function isCancelled(string $shipmentId): bool
    {
        return isset($this->cancelled[$shipmentId]);
    }
}
