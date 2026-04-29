<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class NotSpecification extends CompositeSpecification
{
    /** @param SpecificationInterface<T> $inner */
    public function __construct(
        private readonly SpecificationInterface $inner,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return ! $this->inner->isSatisfiedBy($candidate);
    }
}
