<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Support;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;

final readonly class YiiMethod
{
    public function __construct(
        private ClassMethod $node,
    ) {
        //
    }

    public function node(): ClassMethod
    {
        return $this->node;
    }

    public function name(): string
    {
        return $this->node->name->toString();
    }

    public function line(): int
    {
        return $this->node->getStartLine();
    }

    public function hasParentCall(?string $methodName = null): bool
    {
        $targetMethodName = mb_strtolower($methodName ?? $this->name());
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($this->node->stmts ?? [], StaticCall::class) as $call) {
            if ($this->isParentCall($call, $targetMethodName)) {
                return true;
            }
        }

        return false;
    }

    public function hasIgnoredParentCallResult(?string $methodName = null): bool
    {
        $targetMethodName = mb_strtolower($methodName ?? $this->name());
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($this->node->stmts ?? [], Expression::class) as $statement) {
            if (!$statement->expr instanceof StaticCall) {
                continue;
            }

            if ($this->isParentCall($statement->expr, $targetMethodName)) {
                return true;
            }
        }

        return false;
    }

    private function isParentCall(StaticCall $call, string $methodName): bool
    {
        if (!$call->class instanceof Name) {
            return false;
        }

        if (!$call->name instanceof Identifier) {
            return false;
        }

        if (mb_strtolower($call->class->toString()) !== 'parent') {
            return false;
        }

        return mb_strtolower($call->name->toString()) === $methodName;
    }

    public function callsThisMethod(string $methodName): bool
    {
        return $this->callsAnyThisMethod([$methodName]);
    }

    /**
     * Checks whether this method contains a call to any of the given methods on $this.
     *
     * @param list<string> $methodNames Method names to match case-insensitively.
     */
    public function callsAnyThisMethod(array $methodNames): bool
    {
        $targetMethodNames = array_map(
            mb_strtolower(...),
            $methodNames,
        );
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($this->node->stmts ?? [], MethodCall::class) as $call) {
            if (!$call->var instanceof Variable) {
                continue;
            }

            if ($call->var->name !== 'this') {
                continue;
            }

            if (!$call->name instanceof Identifier) {
                continue;
            }

            if (in_array(mb_strtolower($call->name->toString()), $targetMethodNames, strict: true)) {
                return true;
            }
        }

        return false;
    }
}
