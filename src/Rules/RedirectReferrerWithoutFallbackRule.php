<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class RedirectReferrerWithoutFallbackRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @param Scope      $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || $node->name->toString() !== 'redirect') {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        if ($this->hasFallback($node->args[0]->value)) {
            return [];
        }

        if (!$this->isReferrerExpression($node->args[0]->value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not redirect to request referrer without fallback route and allowlist validation.')
                ->identifier('yii.redirectReferrerWithoutFallback')
                ->build(),
        ];
    }

    private function hasFallback(Expr $expr): bool
    {
        return $expr instanceof Ternary || $expr instanceof Coalesce;
    }

    private function isReferrerExpression(Expr $expr): bool
    {
        if ($expr instanceof Variable && is_string($expr->name)) {
            return mb_strtolower($expr->name) === 'referrer';
        }

        if ($expr instanceof PropertyFetch && $expr->name instanceof Identifier) {
            return mb_strtolower($expr->name->toString()) === 'referrer';
        }

        if (!$expr instanceof MethodCall || !$expr->name instanceof Identifier) {
            return false;
        }

        return in_array(mb_strtolower($expr->name->toString()), ['getreferrer', 'referrer'], true);
    }
}
