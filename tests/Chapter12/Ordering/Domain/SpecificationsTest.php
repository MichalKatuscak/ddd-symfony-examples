<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Ordering\Domain;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\DigitalItem;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\EligibleForFreeShipping;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\InEUCountry;
use App\Chapter12_LesserPatterns\Ordering\Domain\Specification\NotInBlacklist;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use App\Tests\Chapter12\Ordering\OrderMother;
use PHPUnit\Framework\TestCase;

final class SpecificationsTest extends TestCase
{
    public function test_free_shipping_threshold_is_inclusive(): void
    {
        $spec = new EligibleForFreeShipping(new Money(100_000, Currency::CZK));

        self::assertTrue($spec->isSatisfiedBy(OrderMother::physical(100_000)));
        self::assertFalse($spec->isSatisfiedBy(OrderMother::physical(99_999)));
    }

    public function test_threshold_in_other_currency_never_matches(): void
    {
        $spec = new EligibleForFreeShipping(new Money(1, Currency::EUR));

        self::assertFalse($spec->isSatisfiedBy(OrderMother::physical(500_000)));
    }

    public function test_in_eu_country_reads_shipping_address(): void
    {
        self::assertTrue((new InEUCountry())->isSatisfiedBy(OrderMother::physical(1_000, 'SK')));
        self::assertFalse((new InEUCountry())->isSatisfiedBy(OrderMother::physical(1_000, 'US')));
    }

    public function test_digital_order_without_address_is_not_shipped_to_eu(): void
    {
        $order = Order::placeDigital(
            CustomerId::generate(),
            [new DigitalItem(ProductId::generate(), new Money(200_000, Currency::CZK))],
            new \DateTimeImmutable(),
        );

        self::assertFalse((new InEUCountry())->isSatisfiedBy($order));
    }

    public function test_not_in_blacklist_compares_customer_ids(): void
    {
        $blocked = CustomerId::generate();
        $spec = new NotInBlacklist([$blocked]);

        self::assertFalse($spec->isSatisfiedBy(OrderMother::physical(1_000, customer: CustomerId::fromString($blocked->value))));
        self::assertTrue($spec->isSatisfiedBy(OrderMother::physical(1_000)));
    }
}
