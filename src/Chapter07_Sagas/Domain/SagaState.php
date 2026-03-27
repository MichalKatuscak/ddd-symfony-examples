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
}
