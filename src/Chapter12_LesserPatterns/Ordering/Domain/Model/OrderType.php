<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Model;

enum OrderType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
}
