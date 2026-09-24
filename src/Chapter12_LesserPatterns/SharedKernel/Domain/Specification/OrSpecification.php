<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification;

/**
 * @template T
 * @extends CompositeSpecification<T>
 */
final class OrSpecification extends CompositeSpecification
{
    /**
     * @param Specification<T> $left
     * @param Specification<T> $right
     */
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            || $this->right->isSatisfiedBy($candidate);
    }
}
