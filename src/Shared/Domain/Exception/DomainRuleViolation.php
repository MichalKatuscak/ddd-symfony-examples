<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * Společný předek doménových výjimek napříč ukázkami. Konkrétní pravidlo
 * nese vždy pojmenovaný potomek – volající se rozhoduje podle typu,
 * ne podle textu zprávy.
 */
abstract class DomainRuleViolation extends \DomainException
{
}
