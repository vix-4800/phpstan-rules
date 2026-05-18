<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

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
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class RawSqlConditionWithVariableRule implements Rule
{
    private const array RAW_STRING_ARGUMENTS = [
        'where' => [0],
        'andWhere' => [0],
        'orWhere' => [0],
        'having' => [0],
        'filterWhere' => [0],
        'andFilterWhere' => [0],
        'orFilterWhere' => [0],
        'on' => [0],
        'from' => [0],
        'orderBy' => [0],
        'join' => [1, 2],
        'leftJoin' => [0, 1],
        'rightJoin' => [0, 1],
        'innerJoin' => [0, 1],
        'createCommand' => [0],
    ];

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
        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        $argumentIndexes = self::RAW_STRING_ARGUMENTS[$methodName] ?? null;

        if ($argumentIndexes === null) {
            return [];
        }

        foreach ($argumentIndexes as $argumentIndex) {
            if (!isset($node->args[$argumentIndex])) {
                continue;
            }

            if (!$node->args[$argumentIndex] instanceof Arg) {
                continue;
            }

            if (!$this->containsVariableSqlString($node->args[$argumentIndex]->value)) {
                continue;
            }

            return [
                RuleErrorBuilder::message('Do not build raw SQL strings with variables; use hash/operator format or bound params.')
                    ->identifier('yii.rawSqlConditionWithVariable')
                    ->build(),
            ];
        }

        return [];
    }

    private function containsVariableSqlString(Expr $expr): bool
    {
        if ($expr instanceof InterpolatedString) {
            return array_any(
                $expr->parts,
                static fn(Node $part): bool => $part instanceof Expr,
            );
        }

        if ($expr instanceof Concat) {
            if ($this->containsVariableValue($expr->left)) {
                return true;
            }

            return $this->containsVariableValue($expr->right);
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
            if ($this->containsVariableValue($expr->left)) {
                return true;
            }

            return $this->containsVariableValue($expr->right);
        }

        return $expr instanceof InterpolatedString;
    }
}
