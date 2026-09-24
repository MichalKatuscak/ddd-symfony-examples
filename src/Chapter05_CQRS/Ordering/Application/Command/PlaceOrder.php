<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command je prosté DTO s primitivy (kanonická podoba z kapitoly Outbox
 * Pattern). Musí bezpečně projít i asynchronním kanálem.
 */
final readonly class PlaceOrder
{
    /**
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public function __construct(
        #[Assert\Uuid]
        public string $customerId,

        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Collection([
                // NotBlank tu není navíc: Assert\Uuid prázdný řetězec
                // propustí bez porušení.
                'productId' => [new Assert\NotBlank(), new Assert\Uuid()],
                'quantity' => [new Assert\Positive()],
                'unitPriceInCents' => [new Assert\PositiveOrZero()],
            ]),
        ])]
        public array $items,
    ) {}
}
