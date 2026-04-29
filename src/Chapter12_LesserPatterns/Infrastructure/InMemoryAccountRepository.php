<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Infrastructure;

use App\Chapter12_LesserPatterns\Domain\Service\Account;
use App\Chapter12_LesserPatterns\Domain\Service\AccountRepositoryInterface;

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function save(Account $account): void
    {
        $this->accounts[$account->id()] = $account;
    }

    public function findById(string $id): Account
    {
        return $this->accounts[$id]
            ?? throw new \RuntimeException(sprintf('Account "%s" not found.', $id));
    }

    /** @return list<Account> */
    public function findAll(): array
    {
        return array_values($this->accounts);
    }
}
