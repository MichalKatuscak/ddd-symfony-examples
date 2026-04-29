<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\EvaluatePromo;

use App\Chapter12_LesserPatterns\Domain\Order\InEUCountry;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\NotInBlacklist;
use App\Chapter12_LesserPatterns\Domain\Order\OrderEligibleForFreeShipping;
use App\Chapter12_LesserPatterns\Domain\Order\OrderId;
use App\Chapter12_LesserPatterns\Domain\Order\OrderRepositoryInterface;

/**
 * Application Handler — vyhodnotí, jestli má objednávka nárok na promo akci.
 *
 * Demonstruje sílu Specification Patternu: business pravidlo
 * "free shipping AND ships to EU AND nesměřuje na blacklist"
 * vznikne kompozicí tří atomických specifikací místo zatuhlého if/elseif.
 */
final class EvaluatePromoHandler
{
    /** @var list<string> */
    private const BLACKLISTED_COUNTRIES = ['KP', 'IR', 'SY'];

    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function __invoke(EvaluatePromoCommand $command): EvaluatePromoResult
    {
        $order = $this->orders->findById(new OrderId($command->orderId))
            ?? throw new \DomainException(sprintf('Order %s not found', $command->orderId));

        $eligibleForFreeShipping = new OrderEligibleForFreeShipping(
            new Money($command->freeShippingThresholdMinorUnits),
        );
        $inEU = new InEUCountry();
        $notInBlacklist = new NotInBlacklist(self::BLACKLISTED_COUNTRIES);

        // Kompozice — pravidlo vzniká skládáním atomů, ne novou monolitickou metodou.
        $promoSpec = $eligibleForFreeShipping
            ->and($inEU)
            ->and($notInBlacklist);

        return new EvaluatePromoResult(
            eligible: $promoSpec->isSatisfiedBy($order),
            aboveFreeShippingThreshold: $eligibleForFreeShipping->isSatisfiedBy($order),
            shipsToEU: $inEU->isSatisfiedBy($order),
            notInBlacklist: $notInBlacklist->isSatisfiedBy($order),
            orderTotalFormatted: $order->total()->formatted(),
            shippingCountry: $order->shippingCountry(),
        );
    }
}
