<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Peníze podle knihy (Základní koncepty, 06.04). Částka je celé číslo
 * v nejmenších jednotkách měny, měna je enum. Kniha třídu vede v Shared
 * Kernelu (App\SharedKernel\Domain), ukázky ve sdíleném App\Shared\Domain.
 */
final readonly class Money
{
    public function __construct(
        public int $amountInCents,
        public Currency $currency,
    ) {
        if ($amountInCents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative');
        }
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException(
                "Cannot add {$this->currency->value} and {$other->currency->value}"
            );
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException(
                "Cannot subtract {$other->currency->value} from {$this->currency->value}"
            );
        }

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountInCents * $factor, $this->currency);
    }

    /** Procentní podíl. Sazby jsou celá procenta, dělení zaokrouhluje nahoru. */
    public function percentage(int $percent): self
    {
        return new self(intdiv($this->amountInCents * $percent + 99, 100), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }
}
