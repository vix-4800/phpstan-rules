<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

final readonly class QueryChainInspector
{
    /**
     * @param MethodCall   $terminalCall
     * @param list<string> $terminalMethods
     * @param list<string> $limitMethods
     */
    public function isUnboundedQueryCall(
        MethodCall $terminalCall,
        array $terminalMethods,
        array $limitMethods = ['limit', 'page'],
    ): bool {
        if (!$terminalCall->name instanceof Identifier) {
            return false;
        }

        if (!in_array($terminalCall->name->toString(), $terminalMethods, true)) {
            return false;
        }

        $hasLimit = false;
        $expr = $terminalCall->var;

        while ($expr instanceof MethodCall) {
            if ($expr->name instanceof Identifier) {
                $hasLimit = $hasLimit || in_array($expr->name->toString(), $limitMethods, true);
            }

            $expr = $expr->var;
        }

        if ($hasLimit) {
            return false;
        }

        return $this->isQuerySource($expr);
    }

    private function isQuerySource(Expr $expr): bool
    {
        if ($expr instanceof StaticCall && $expr->name instanceof Identifier) {
            return $expr->name->toString() === 'find';
        }

        if (!$expr instanceof New_) {
            return false;
        }

        if (!$expr->class instanceof Name) {
            return false;
        }

        $className = mb_ltrim($expr->class->toString(), '\\');

        return in_array($className, ['yii\db\Query', 'Query'], true)
            || str_ends_with($className, '\Query');
    }
}
