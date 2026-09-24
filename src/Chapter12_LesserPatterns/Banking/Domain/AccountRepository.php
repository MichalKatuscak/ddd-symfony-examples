<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain;

interface AccountRepository
{
    public function save(Account $account): void;

    public function get(AccountId $id): Account;
}
