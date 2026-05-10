<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final readonly class YiiControllerRuleHelper
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function isYiiController(Class_ $class, Scope $scope): bool
    {
        if ($class->namespacedName === null && $class->extends === null) {
            return false;
        }

        if ($class->namespacedName !== null) {
            $className = $class->namespacedName->toString();
        } elseif ($class->extends !== null) {
            $className = $scope->resolveName($class->extends);
        } else {
            return false;
        }

        if ($class->extends !== null && $this->isControllerClassName($scope->resolveName($class->extends))) {
            return true;
        }

        return $this->isSubclassOf($className, 'yii\\base\\Controller')
            || $this->isSubclassOf($className, 'yii\\web\\Controller')
            || $this->isSubclassOf($className, 'yii\\rest\\Controller');
    }

    /**
     * @return list<ClassMethod>
     */
    public function getActionMethods(Class_ $class): array
    {
        $actions = [];

        foreach ($class->getMethods() as $method) {
            $methodName = $method->name->toString();

            if (!$method->isPublic() || !str_starts_with($methodName, 'action') || $methodName === 'actions') {
                continue;
            }

            if (strlen($methodName) === strlen('action')) {
                continue;
            }

            $actions[] = $method;
        }

        return $actions;
    }

    public function getActionId(ClassMethod $action): string
    {
        $actionName = substr($action->name->toString(), strlen('action'));
        $actionId = preg_replace('/(?<!^)[A-Z]/', '-$0', $actionName);

        return strtolower($actionId ?? $actionName);
    }

    /**
     * @return list<Array_>
     */
    public function getBehaviorsByClass(Class_ $class, string $behaviorClassName): array
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'behaviors') {
                continue;
            }

            return $this->getMethodBehaviorsByClass($method, $behaviorClassName);
        }

        return [];
    }

    public function behaviorAppliesToAction(Array_ $behavior, string $actionId): bool
    {
        $only = $this->getStringListItem($behavior, 'only');

        if ($only !== null && !in_array($actionId, $only, true)) {
            return false;
        }

        $except = $this->getStringListItem($behavior, 'except');

        return $except === null || !in_array($actionId, $except, true);
    }

    /**
     * @return list<string>|null
     */
    public function getStringListItem(Array_ $array, string $key): ?array
    {
        $value = $this->getArrayItem($array, $key);

        if (!$value instanceof Array_) {
            return null;
        }

        $strings = [];

        foreach ($value->items as $item) {
            if (!$item->value instanceof String_) {
                return null;
            }

            $strings[] = $item->value->value;
        }

        return $strings;
    }

    public function getArrayItem(Array_ $array, string $key): ?Node\Expr
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== $key) {
                continue;
            }

            return $item->value;
        }

        return null;
    }

    /**
     * @return list<Array_>
     */
    private function getMethodBehaviorsByClass(ClassMethod $method, string $behaviorClassName): array
    {
        $finder = new NodeFinder();
        $behaviors = [];

        foreach ($finder->findInstanceOf($method->stmts ?? [], Array_::class) as $array) {
            if (!$this->isBehaviorClass($array, $behaviorClassName)) {
                continue;
            }

            $behaviors[] = $array;
        }

        return $behaviors;
    }

    private function isBehaviorClass(Array_ $array, string $behaviorClassName): bool
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== 'class') {
                continue;
            }

            return $this->isClassNameValue($item->value, $behaviorClassName);
        }

        return false;
    }

    private function isClassNameValue(Node\Expr $value, string $className): bool
    {
        if (
            $value instanceof ClassConstFetch
            && $value->name instanceof Node\Identifier
            && $value->name->toString() === 'class'
            && $value->class instanceof Name
        ) {
            return $this->isClassName($value->class, $className);
        }

        if ($value instanceof String_) {
            return ltrim($value->value, '\\') === $className;
        }

        if ($value instanceof ConstFetch) {
            return $this->isClassName($value->name, $className);
        }

        return false;
    }

    private function isClassName(Name $name, string $className): bool
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return ltrim($resolvedName->toString(), '\\') === $className;
        }

        return ltrim($name->toString(), '\\') === $className
            || substr($className, strrpos($className, '\\') + 1) === $name->toString();
    }

    private function isControllerClassName(string $className): bool
    {
        return in_array(
            ltrim($className, '\\'),
            ['yii\\base\\Controller', 'yii\\web\\Controller', 'yii\\rest\\Controller'],
            true,
        );
    }

    private function isSubclassOf(string $className, string $parentClassName): bool
    {
        if (!$this->reflectionProvider->hasClass($className) || !$this->reflectionProvider->hasClass($parentClassName)) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($className);
        $parentClass = $this->reflectionProvider->getClass($parentClassName);

        return $class->getName() === $parentClass->getName() || $class->isSubclassOfClass($parentClass);
    }
}
