<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Command;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

/** Tvar z kapitoly o autorizaci: identita aktéra cestuje v příkazu. */
final readonly class CancelOrderCommand
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
        public CustomerId $actorId,
    ) {}
}
