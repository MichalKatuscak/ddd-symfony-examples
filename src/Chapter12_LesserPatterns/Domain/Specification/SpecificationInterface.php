<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Specification;

/**
 * Doménová specifikace — prvotřídní objekt zapouzdřující booleovský predikát
 * nad doménovým konceptem. Lze ji skládat pomocí kombinátorů and/or/not.
 *
 * @template T
 */
interface SpecificationInterface
{
    /** @param T $candidate */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * @param SpecificationInterface<T> $other
     * @return SpecificationInterface<T>
     */
    public function and(self $other): self;

    /**
     * @param SpecificationInterface<T> $other
     * @return SpecificationInterface<T>
     */
    public function or(self $other): self;

    /** @return SpecificationInterface<T> */
    public function not(): self;
}
