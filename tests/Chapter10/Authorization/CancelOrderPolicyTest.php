<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Authorization;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter10_Authorization\Authorization\CancelOrderPolicy;
use App\Chapter10_Authorization\Authorization\PolicyContext;
use App\Chapter10_Authorization\Authorization\PolicyEvaluator;
use App\Tests\Chapter10\Domain\OrderFactory;
use App\Tests\Chapter10\Identity\SecurityUserFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CancelOrderPolicyTest extends TestCase
{
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    /** @return iterable<string, array{subject: object, user: object, expected: ?string}> */
    public static function scenarios(): iterable
    {
        yield 'happy path' => [
            'subject'  => OrderFactory::placedFor(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => null,
        ];
        yield 'wrong customer' => [
            'subject'  => OrderFactory::placedFor(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::STRANGER),
            'expected' => 'Pouze vlastník objednávky',
        ];
        yield 'shipped order' => [
            'subject'  => OrderFactory::shipped(CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => 'Objednávka musí být potvrzená',
        ];
        yield 'window expired' => [
            'subject'  => OrderFactory::placed('2026-04-28 09:00:00', CustomerId::fromString(self::OWNER)),
            'user'     => SecurityUserFixture::for(self::OWNER),
            'expected' => 'Storno lhůta 24 h ještě neuplynula',
        ];
    }

    #[DataProvider('scenarios')]
    public function testEvaluate(object $subject, object $user, ?string $expected): void
    {
        $evaluator = new PolicyEvaluator();
        // Čas vyhodnocení je pevný, jinak by scénář se lhůtou po roce
        // začal padat sám od sebe.
        $context = new PolicyContext($subject, $user, new \DateTimeImmutable('2026-04-29 12:00:00'));

        $violation = $evaluator->evaluate(new CancelOrderPolicy(), $context);

        self::assertSame($expected, $violation?->description);
    }
}
