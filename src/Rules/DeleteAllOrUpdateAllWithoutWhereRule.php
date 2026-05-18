<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Expr>
 */
final readonly class DeleteAllOrUpdateAllWithoutWhereRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (!in_array($methodName, ['deleteAll', 'updateAll'], true)) {
            return [];
        }

        $conditionArgument = $this->getConditionArgument($node->args, $methodName);

        if ($conditionArgument instanceof Arg && !$this->isEmptyCondition($conditionArgument->value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Do not call %s() without a non-empty condition.',
                $methodName,
            ))
                ->identifier('yii.deleteAllOrUpdateAllWithoutWhere')
                ->build(),
        ];
    }

    /**
     * @param array<int|string, Arg|VariadicPlaceholder> $args
     * @param string                                     $methodName
     */
    private function getConditionArgument(array $args, string $methodName): ?Arg
    {
        foreach ($args as $arg) {
            if ($arg instanceof Arg && $arg->name instanceof Identifier && $arg->name->toString() === 'condition') {
                return $arg;
            }
        }

        $conditionArgumentIndex = $methodName === 'deleteAll' ? 0 : 1;

        $arg = $args[$conditionArgumentIndex] ?? null;

        return $arg instanceof Arg ? $arg : null;
    }

    private function isEmptyCondition(Expr $condition): bool
    {
        if ($condition instanceof String_) {
            return $condition->value === '';
        }

        if ($condition instanceof Array_) {
            return $condition->items === [];
        }

        return $condition instanceof ConstFetch
            && mb_strtolower($condition->name->toString()) === 'null';
    }
}
