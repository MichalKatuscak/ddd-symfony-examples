<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Domain;

enum SagaState: string
{
    case Started = 'started';
    case StockReserved = 'stock_reserved';
    case PaymentProcessed = 'payment_processed';
    case Shipped = 'shipped';
    case Compensating = 'compensating';
    case Failed = 'failed';
    case Completed = 'completed';

    /**
     * Z terminálního stavu už sága nikam nepokračuje. Opožděná událost
     * ji nesmí vzkřísit – proto se na tuhle otázku ptá každý krok.
     */
    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
