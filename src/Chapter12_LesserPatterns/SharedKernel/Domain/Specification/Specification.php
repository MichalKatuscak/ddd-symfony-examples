<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification;

/**
 * Doménová specifikace – prvotřídní objekt zapouzdřující booleovský predikát.
 *
 * @template T
 */
interface Specification
{
    /** @param T $candidate */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * @param Specification<T> $other
     * @return Specification<T>
     */
    public function and(self $other): self;

    /**
     * @param Specification<T> $other
     * @return Specification<T>
     */
    public function or(self $other): self;

    /** @return Specification<T> */
    public function not(): self;
}
