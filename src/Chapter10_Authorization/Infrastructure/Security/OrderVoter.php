<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Infrastructure\Security;

use App\Chapter02_AggregateDesign\Domain\Order\Order;

/**
 * Zjednodušený Voter bez závislosti na Symfony Security, aby šel spustit
 * unit testem. Rozhodovací logika je tatáž, jakou popisuje příručka.
 */
final readonly class OrderVoter
{
    public const VIEW = 'order.view';
    public const CANCEL = 'order.cancel';

    public const GRANTED = 1;
    public const ABSTAIN = 0;
    public const DENIED = -1;

    public function vote(SecurityUser $user, mixed $subject, string $attribute): int
    {
        // Voter, který subjekt nezná, se zdrží – nesmí ho načítat sám.
        if (!$subject instanceof Order) {
            return self::ABSTAIN;
        }

        if (!in_array($attribute, [self::VIEW, self::CANCEL], true)) {
            return self::ABSTAIN;
        }

        // Vlastnictví je vztah, který zná agregát. Voter se na něj ptá,
        // neopisuje ho.
        return $subject->isOwnedBy($user->customerId()) ? self::GRANTED : self::DENIED;
    }
}
