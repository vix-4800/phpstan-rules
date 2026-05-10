<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class MassSelectionWithoutLimitRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isUnboundedFindAll($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not call find()->all() without limit().')
                ->identifier('yii.massSelectionWithoutLimit')
                ->build(),
        ];
    }

    private function isUnboundedFindAll(MethodCall $methodCall): bool
    {
        if (!$methodCall->name instanceof Node\Identifier || $methodCall->name->toString() !== 'all') {
            return false;
        }

        $hasFind = false;
        $hasLimit = false;
        $expr = $methodCall->var;

        while ($expr instanceof MethodCall) {
            if ($expr->name instanceof Node\Identifier) {
                $methodName = $expr->name->toString();
                $hasLimit = $hasLimit || in_array($methodName, ['limit', 'page'], true);
            }

            $expr = $expr->var;
        }

        if ($expr instanceof StaticCall && $expr->name instanceof Node\Identifier) {
            $hasFind = $expr->name->toString() === 'find';
        }

        return $hasFind && !$hasLimit;
    }
}
