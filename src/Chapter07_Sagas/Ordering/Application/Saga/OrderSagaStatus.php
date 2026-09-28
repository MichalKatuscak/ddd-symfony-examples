<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Saga;

enum OrderSagaStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case AwaitingStockReservation = 'awaiting_stock_reservation';
    case AwaitingShipment = 'awaiting_shipment';
    case Completed = 'completed';
    case Compensating = 'compensating';
    case Failed = 'failed';

    /**
     * Z terminálního stavu už sága nikam nepokračuje. Opožděná událost
     * ji nesmí vzkřísit – proto se na tuto otázku ptá každý handler
     * hned na začátku.
     */
    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
