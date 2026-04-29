<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Infrastructure;

use App\Chapter12_LesserPatterns\Domain\Customer\Customer;
use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Customer\CustomerRepositoryInterface;

final class InMemoryCustomerRepository implements CustomerRepositoryInterface
{
    /** @var array<string, Customer> */
    private array $customers = [];

    public function save(Customer $customer): void
    {
        $this->customers[$customer->id()->value] = $customer;
    }

    public function findById(CustomerId $id): ?Customer
    {
        return $this->customers[$id->value] ?? null;
    }
}
