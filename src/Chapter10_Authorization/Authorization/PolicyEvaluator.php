<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Authorization;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class PolicyEvaluator
{
    public function __construct(private readonly ExpressionLanguage $expr = new ExpressionLanguage()) {}

    /**
     * Vrací první porušené pravidlo, nebo null pokud všechna prošla.
     */
    public function evaluate(Policy $policy, PolicyContext $ctx): ?Rule
    {
        $vars = [
            'subject' => $ctx->subject,
            'user'    => $ctx->user,
            'now'     => $ctx->now->getTimestamp(),
        ];
        foreach ($policy->rules() as $rule) {
            if (!$this->expr->evaluate($rule->expression, $vars)) {
                return $rule;
            }
        }
        return null;
    }
}
