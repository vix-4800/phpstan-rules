<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Support;

use PhpParser\Node\Stmt\Class_;

final readonly class YiiActiveRecord
{
    /**
     * @param Class_          $node
     * @param list<YiiMethod> $lifecycleMethods
     */
    public function __construct(
        private Class_ $node,
        private array $lifecycleMethods,
    ) {
        //
    }

    public function node(): Class_
    {
        return $this->node;
    }

    /**
     * @return list<YiiMethod>
     */
    public function lifecycleMethods(): array
    {
        return $this->lifecycleMethods;
    }

    public function lifecycleMethod(string $methodName): ?YiiMethod
    {
        foreach ($this->lifecycleMethods as $method) {
            if ($method->name() === $methodName) {
                return $method;
            }
        }

        return null;
    }

    public function hasLifecycleMethod(string $methodName): bool
    {
        return $this->lifecycleMethod($methodName) !== null;
    }
}
