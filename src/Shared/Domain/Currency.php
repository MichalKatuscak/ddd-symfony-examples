<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/** Měna jako uzavřený výčet. Hodnota se čte přes ->value. */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
}
