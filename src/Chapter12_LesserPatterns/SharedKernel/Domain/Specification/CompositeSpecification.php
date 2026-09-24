<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification;

/**
 * Konkrétní specifikace kombinátory neimplementují, dodá je tato třída.
 *
 * @template T
 * @implements Specification<T>
 */
abstract class CompositeSpecification implements Specification
{
    /** @param T $candidate */
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }

    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }

    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}
