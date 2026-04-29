<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Domain\Specification;

use App\Chapter12_LesserPatterns\Domain\Specification\CompositeSpecification;
use PHPUnit\Framework\TestCase;

final class AndSpecificationTest extends TestCase
{
    public function test_true_AND_true_yields_true(): void
    {
        $spec = $this->trueSpec()->and($this->trueSpec());

        $this->assertTrue($spec->isSatisfiedBy(new \stdClass()));
    }

    public function test_true_AND_false_yields_false(): void
    {
        $spec = $this->trueSpec()->and($this->falseSpec());

        $this->assertFalse($spec->isSatisfiedBy(new \stdClass()));
    }

    public function test_false_AND_true_yields_false(): void
    {
        $spec = $this->falseSpec()->and($this->trueSpec());

        $this->assertFalse($spec->isSatisfiedBy(new \stdClass()));
    }

    public function test_or_combinator_yields_true_when_any_true(): void
    {
        $spec = $this->falseSpec()->or($this->trueSpec());

        $this->assertTrue($spec->isSatisfiedBy(new \stdClass()));
    }

    public function test_not_combinator_inverts_inner_spec(): void
    {
        $spec = $this->trueSpec()->not();

        $this->assertFalse($spec->isSatisfiedBy(new \stdClass()));
    }

    public function test_chained_composition_evaluates_left_to_right(): void
    {
        // (true AND true) AND false  →  false
        $spec = $this->trueSpec()->and($this->trueSpec())->and($this->falseSpec());

        $this->assertFalse($spec->isSatisfiedBy(new \stdClass()));
    }

    private function trueSpec(): CompositeSpecification
    {
        return new class extends CompositeSpecification {
            public function isSatisfiedBy(mixed $candidate): bool
            {
                return true;
            }
        };
    }

    private function falseSpec(): CompositeSpecification
    {
        return new class extends CompositeSpecification {
            public function isSatisfiedBy(mixed $candidate): bool
            {
                return false;
            }
        };
    }
}
