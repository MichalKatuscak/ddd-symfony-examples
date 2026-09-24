<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\ViewModel;

/**
 * Řádek přehledu objednávek. Jen data pro prezentaci, žádná doménová
 * logika; `status` nese slovník obrazovky (placed, shipped, cancelled),
 * ne hodnoty enumu OrderStatus.
 */
final readonly class OrderSummaryViewModel
{
    public function __construct(
        public string $orderId,
        public string $status,
        public int $totalAmountInCents,
        public ?string $shipmentId,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
