<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Infrastructure\Security;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter10_Authorization\Infrastructure\Security\OrderVoter;
use App\Tests\Chapter10\Domain\OrderFactory;
use App\Tests\Chapter10\Identity\SecurityUserFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoterTest extends TestCase
{
    // Identifikátory jsou UUID – CustomerId jinou hodnotu nepřijme.
    private const OWNER    = '018f4d2e-7a31-7c9e-b4d0-6f2a1c8e5b03';
    private const STRANGER = '02b5e8c1-9d44-7f10-a8b7-3e5c9d21f746';

    public function testOwnerCanCancelOwnOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));

        self::assertSame(
            Voter::ACCESS_GRANTED,
            $this->voteCancel($order, actor: self::OWNER)
        );
    }

    public function testStrangerCannotCancelOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));

        self::assertSame(
            Voter::ACCESS_DENIED,
            $this->voteCancel($order, actor: self::STRANGER)
        );
    }

    public function testDenialCarriesReason(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));
        $vote = new Vote();

        $this->voteCancel($order, actor: self::STRANGER, vote: $vote);

        // Vote::$reasons je veřejná vlastnost, ne getter
        self::assertSame(['Objednávku smí zrušit pouze její vlastník.'], $vote->reasons);
    }

    public function testVoterAbstainsOnForeignSubject(): void
    {
        // Voter nerozhoduje o něčem, co nezná.
        self::assertSame(
            Voter::ACCESS_ABSTAIN,
            $this->voteCancel(new \stdClass(), actor: self::OWNER)
        );
    }

    public function testAdminMayViewForeignOrder(): void
    {
        $order = OrderFactory::placedFor(CustomerId::fromString(self::OWNER));

        $decisions = $this->createStub(AccessDecisionManagerInterface::class);
        $decisions->method('decide')->willReturn(true); // aktér má ROLE_ADMIN

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(SecurityUserFixture::for(self::STRANGER, 'ROLE_ADMIN'));

        self::assertSame(
            Voter::ACCESS_GRANTED,
            (new OrderVoter($decisions))->vote($token, $order, [OrderVoter::VIEW]),
        );
    }

    private function voteCancel(object $order, string $actor, ?Vote $vote = null): int
    {
        // Bez očekávání jde o stuby, ne mocky – createMock() by na PHPUnit 13
        // hlásil „No expectations were configured“.
        $decisions = $this->createStub(AccessDecisionManagerInterface::class);
        $decisions->method('decide')->willReturn(false); // aktér nemá žádnou roli navíc

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(SecurityUserFixture::for($actor));

        return (new OrderVoter($decisions))->vote($token, $order, [OrderVoter::CANCEL], $vote);
    }
}
