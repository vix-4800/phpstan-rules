<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final readonly class TransactionWithoutRollbackHandlingRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassMethod) {
            return [];
        }

        $beginTransactionCall = $this->findBeginTransactionCall($node);

        if (!$beginTransactionCall instanceof MethodCall || $this->hasRollbackHandling($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Method starts a Yii transaction with beginTransaction() but does not call rollBack()/rollback() in a catch block.',
            )
                ->identifier('yii.transactionWithoutRollbackHandling')
                ->line($beginTransactionCall->getStartLine())
                ->build(),
        ];
    }

    private function findBeginTransactionCall(ClassMethod $method): ?MethodCall
    {
        $finder = new NodeFinder();
        $call = $finder->findFirst(
            $method->stmts ?? [],
            static fn(Node $node): bool => $node instanceof MethodCall
                && $node->name instanceof Identifier
                && $node->name->toString() === 'beginTransaction',
        );

        return $call instanceof MethodCall ? $call : null;
    }

    private function hasRollbackHandling(ClassMethod $method): bool
    {
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($method->stmts ?? [], TryCatch::class) as $tryCatch) {
            foreach ($tryCatch->catches as $catch) {
                $rollbackCall = $finder->findFirst(
                    $catch->stmts,
                    static fn(Node $node): bool => $node instanceof MethodCall
                        && $node->name instanceof Identifier
                        && mb_strtolower($node->name->toString()) === 'rollback',
                );

                if ($rollbackCall instanceof MethodCall) {
                    return true;
                }
            }
        }

        return false;
    }
}
