<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class OrSpecification extends CompositeSpecification
{
    /**
     * @param SpecificationInterface<T> $left
     * @param SpecificationInterface<T> $right
     */
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            || $this->right->isSatisfiedBy($candidate);
    }
}
