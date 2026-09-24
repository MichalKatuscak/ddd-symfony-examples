<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class ListOrders
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $customerId,

        public readonly ?string $status = null,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,

        #[Assert\PositiveOrZero]
        public readonly int $offset = 0,

        public readonly string $sortBy = 'createdAt',
        public readonly string $sortDirection = 'DESC',
    ) {
    }
}
