<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Authorization;

/**
 * ABAC zápis pravidel storna (11.08). Stav a lhůta tu stojí jen pro
 * ilustraci; zdrojem pravdy zůstává agregát, který neplatný příkaz
 * odmítne i bez autorizační vrstvy.
 */
final class CancelOrderPolicy implements Policy
{
    public function name(): string
    {
        return 'order.cancel';
    }

    /** @return list<Rule> */
    public function rules(): array
    {
        return [
            new Rule(
                // user.customerId je privátní – ExpressionLanguage k němu
                // getter nedohledá, volá se metoda.
                expression:  'subject.customerId == user.customerId()',
                description: 'Pouze vlastník objednávky',
            ),
            new Rule(
                expression:  'subject.status.value == "confirmed"',
                description: 'Objednávka musí být potvrzená',
            ),
            new Rule(
                expression:  'subject.placedAt.getTimestamp() >= now - 86400',
                description: 'Storno lhůta 24 h ještě neuplynula',
            ),
        ];
    }
}
