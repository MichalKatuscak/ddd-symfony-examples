<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Command je prosté DTO s primitivy. Přichází z HTTP vrstvy, kde hodnotové
 * objekty ještě neexistují.
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
                // Assert\Uuid prázdný řetězec propustí, proto NotBlank.
                'productId' => [new Assert\NotBlank(), new Assert\Uuid()],
                'quantity' => [new Assert\Positive()],
                'unitPriceInCents' => [new Assert\PositiveOrZero()],
            ]),
        ])]
        public array $items,
    ) {}
}
