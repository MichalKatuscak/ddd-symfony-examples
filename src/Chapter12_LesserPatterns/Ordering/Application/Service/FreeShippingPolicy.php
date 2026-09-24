<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Application\Service;

use App\Chapter12_LesserPatterns\Ordering\Application\BlacklistRegistry;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\EligibleForFreeShipping;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\InEUCountry;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\NotInBlacklist;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * „Doprava zdarma pro nákupy nad 1000 Kč v EU, kromě zákazníků
 * na blacklistu“ – trojice atomických specifikací spojená `and`.
 */
final class FreeShippingPolicy
{
    public function __construct(private readonly BlacklistRegistry $blacklist) {}

    // Politika odpovídá, nemění stav. Co s nárokem udělat (nulové dopravné,
    // slevový řádek), rozhoduje handler checkoutu, který ji volá.
    public function isEligible(Order $order): bool
    {
        // 1000 Kč v haléřích – Money drží částku jako celé číslo.
        $promo = (new EligibleForFreeShipping(new Money(100_000, Currency::CZK)))
            ->and(new InEUCountry())
            ->and(new NotInBlacklist($this->blacklist->all()));

        return $promo->isSatisfiedBy($order);
    }
}
