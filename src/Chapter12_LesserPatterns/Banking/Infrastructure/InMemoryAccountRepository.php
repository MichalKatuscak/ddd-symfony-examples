<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Infrastructure;

use App\Chapter12_LesserPatterns\Banking\Domain\Account;
use App\Chapter12_LesserPatterns\Banking\Domain\AccountId;
use App\Chapter12_LesserPatterns\Banking\Domain\AccountRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: AccountRepository::class)]
final class InMemoryAccountRepository implements AccountRepository
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function save(Account $account): void
    {
        $this->accounts[$account->id()->value] = $account;
    }

    public function get(AccountId $id): Account
    {
        return $this->accounts[$id->value]
            ?? throw new \OutOfBoundsException(sprintf('Účet „%s“ neexistuje.', $id->value));
    }
}
