<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/** Měna jako uzavřený výčet. Kód měny se čte přes ->value, nikdy ->code. */
enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
}
