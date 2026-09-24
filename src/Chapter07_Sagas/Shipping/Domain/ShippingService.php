<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Shipping\Domain;

interface ShippingService
{
    /** @return string identifikátor zásilky */
    public function create(string $orderId): string;

    public function cancel(string $shipmentId): void;
}
