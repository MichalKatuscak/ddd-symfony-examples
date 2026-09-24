<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\SharedKernel;

use App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification\CompositeSpecification;
use App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification\Specification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompositeSpecificationTest extends TestCase
{
    /** @return Specification<int> */
    private static function constant(bool $value): Specification
    {
        return new class ($value) extends CompositeSpecification {
            public function __construct(private readonly bool $value) {}

            public function isSatisfiedBy(mixed $candidate): bool
            {
                return $this->value;
            }
        };
    }

    /** @return iterable<string, array{bool, bool, bool, bool}> */
    public static function truthTable(): iterable
    {
        yield 'T, T' => [true, true, true, true];
        yield 'T, F' => [true, false, false, true];
        yield 'F, T' => [false, true, false, true];
        yield 'F, F' => [false, false, false, false];
    }

    #[DataProvider('truthTable')]
    public function test_and_or_follow_boolean_logic(bool $left, bool $right, bool $and, bool $or): void
    {
        self::assertSame($and, self::constant($left)->and(self::constant($right))->isSatisfiedBy(1));
        self::assertSame($or, self::constant($left)->or(self::constant($right))->isSatisfiedBy(1));
    }

    public function test_not_negates(): void
    {
        self::assertFalse(self::constant(true)->not()->isSatisfiedBy(1));
        self::assertTrue(self::constant(false)->not()->isSatisfiedBy(1));
    }
}
