<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Customer;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function findById(CustomerId $id): ?Customer;
}
