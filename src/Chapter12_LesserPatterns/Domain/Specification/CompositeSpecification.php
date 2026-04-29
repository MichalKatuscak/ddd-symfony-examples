<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Specification;

/**
 * Abstraktní base — poskytuje implementaci kombinátorů and/or/not,
 * aby každá konkrétní specifikace implementovala pouze isSatisfiedBy().
 *
 * @template T
 * @implements SpecificationInterface<T>
 */
abstract class CompositeSpecification implements SpecificationInterface
{
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    public function and(SpecificationInterface $other): SpecificationInterface
    {
        return new AndSpecification($this, $other);
    }

    public function or(SpecificationInterface $other): SpecificationInterface
    {
        return new OrSpecification($this, $other);
    }

    public function not(): SpecificationInterface
    {
        return new NotSpecification($this);
    }
}
