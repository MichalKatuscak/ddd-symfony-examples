<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain;

use App\Chapter12_LesserPatterns\Banking\Domain\Exception\AccountNotFoundException;

interface AccountRepository
{
    public function save(Account $account): void;

    /** @throws AccountNotFoundException když účet neexistuje */
    public function get(AccountId $id): Account;
}
