<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Authorization;

interface Policy
{
    public function name(): string;

    /** @return list<Rule> */
    public function rules(): array;
}
