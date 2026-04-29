<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Service;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(string $id): Account;

    /** @return list<Account> */
    public function findAll(): array;
}
