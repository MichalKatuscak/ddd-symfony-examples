<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Ordering\Application;

use App\Chapter12_LesserPatterns\Ordering\Application\Service\FreeShippingPolicy;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Infrastructure\InMemoryBlacklistRegistry;
use App\Tests\Chapter12\Ordering\OrderMother;
use PHPUnit\Framework\TestCase;

final class FreeShippingPolicyTest extends TestCase
{
    private FreeShippingPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new FreeShippingPolicy(new InMemoryBlacklistRegistry());
    }

    public function test_order_over_1000_czk_to_eu_is_eligible(): void
    {
        self::assertTrue($this->policy->isEligible(OrderMother::physical(100_000, 'CZ')));
    }

    public function test_each_atom_can_break_the_rule(): void
    {
        $blocked = CustomerId::fromString(InMemoryBlacklistRegistry::BLOCKED_CUSTOMER);

        self::assertFalse($this->policy->isEligible(OrderMother::physical(99_999, 'CZ')), 'pod prahem');
        self::assertFalse($this->policy->isEligible(OrderMother::physical(100_000, 'US')), 'mimo EU');
        self::assertFalse($this->policy->isEligible(OrderMother::physical(100_000, 'CZ', $blocked)), 'blacklist');
    }
}
