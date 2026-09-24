<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Infrastructure\Security;

use App\Chapter10_Authorization\Domain\Order\Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter z kapitoly (11.04). Zná identitu uživatele a subjekt; doménové
 * invarianty (stav, lhůtu) nezná – ty vynucuje agregát.
 *
 * @extends Voter<string, Order>
 */
final class OrderVoter extends Voter
{
    public const VIEW   = 'order.view';
    public const CANCEL = 'order.cancel';
    public const REFUND = 'order.refund';

    public function __construct(
        private readonly AccessDecisionManagerInterface $decisions,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true)
            && $subject instanceof Order;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL, self::REFUND], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === Order::class;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();
        if (!$user instanceof SecurityUser) {
            $vote?->addReason('Aktér není přihlášený uživatel aplikace.');
            return false;
        }

        \assert($subject instanceof Order);

        return match ($attribute) {
            self::VIEW   => $this->canView($subject, $user, $token),
            self::CANCEL => $this->canCancel($subject, $user, $vote),
            self::REFUND => $this->decisions->decide($token, ['ROLE_REFUND_AGENT']),
            default      => false,
        };
    }

    private function canView(Order $order, SecurityUser $user, TokenInterface $token): bool
    {
        // Vlastnictví definuje agregát, Voter se jen ptá
        return $order->isOwnedBy($user->customerId())
            || $this->decisions->decide($token, ['ROLE_ADMIN']);
    }

    private function canCancel(Order $order, SecurityUser $user, ?Vote $vote): bool
    {
        if (!$order->isOwnedBy($user->customerId())) {
            // Důvod zamítnutí (Symfony 7.3+) čte aplikační vrstva přes
            // Security::getAccessDecision(); rozhodnutí samo se nemění.
            $vote?->addReason('Objednávku smí zrušit pouze její vlastník.');
            return false;
        }

        return true;
    }
}
