<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Encapsed;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class RawSqlConditionWithVariableRule implements Rule
{
    private const array CONDITION_METHODS = [
        'where',
        'andWhere',
        'orWhere',
        'having',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (!in_array($node->name->toString(), self::CONDITION_METHODS, true)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        if (!$this->containsVariableSqlString($node->args[0]->value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not build raw SQL condition strings with variables; use hash/operator format or bound params.')
                ->identifier('yii.rawSqlConditionWithVariable')
                ->build(),
        ];
    }

    private function containsVariableSqlString(Expr $expr): bool
    {
        if ($expr instanceof Encapsed) {
            return array_any(
                $expr->parts,
                static fn(Node $part): bool => $part instanceof Expr,
            );
        }

        if ($expr instanceof Concat) {
            return $this->containsVariableValue($expr->left) || $this->containsVariableValue($expr->right);
        }

        return false;
    }

    private function containsVariableValue(Expr $expr): bool
    {
        if (
            $expr instanceof Variable
            || $expr instanceof ArrayDimFetch
            || $expr instanceof PropertyFetch
            || $expr instanceof StaticPropertyFetch
        ) {
            return true;
        }

        if ($expr instanceof Concat) {
            return $this->containsVariableValue($expr->left) || $this->containsVariableValue($expr->right);
        }

        if ($expr instanceof Encapsed) {
            return true;
        }

        return false;
    }
}
