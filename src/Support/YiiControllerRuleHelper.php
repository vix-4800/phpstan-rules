<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
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
        //
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

        if ($this->isSubclassOf($className, 'yii\base\Controller')) {
            return true;
        }

        if ($this->isSubclassOf($className, 'yii\web\Controller')) {
            return true;
        }

        return $this->isSubclassOf($className, 'yii\rest\Controller');
    }

    /**
     * @param Class_ $class
     *
     * @return list<ClassMethod>
     */
    public function getActionMethods(Class_ $class): array
    {
        $actions = [];

        foreach ($class->getMethods() as $method) {
            $methodName = $method->name->toString();

            if (!$method->isPublic()) {
                continue;
            }

            if (!str_starts_with($methodName, 'action')) {
                continue;
            }

            if ($methodName === 'actions') {
                continue;
            }

            if (mb_strlen($methodName) === mb_strlen('action')) {
                continue;
            }

            $actions[] = $method;
        }

        return $actions;
    }

    public function getActionId(ClassMethod $action): string
    {
        $actionName = mb_substr($action->name->toString(), mb_strlen('action'));
        $actionId = preg_replace('/(?<!^)[A-Z]/', '-$0', $actionName);

        return mb_strtolower($actionId ?? $actionName);
    }

    /**
     * @param Class_ $class
     * @param string $behaviorClassName
     *
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
     * @param Array_ $array
     * @param string $key
     *
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

    public function getArrayItem(Array_ $array, string $key): ?Expr
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($item->key->value !== $key) {
                continue;
            }

            return $item->value;
        }

        return null;
    }

    /**
     * @param ClassMethod $method
     * @param string      $behaviorClassName
     *
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
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($item->key->value !== 'class') {
                continue;
            }

            return $this->isClassNameValue($item->value, $behaviorClassName);
        }

        return false;
    }

    private function isClassNameValue(Expr $value, string $className): bool
    {
        if (
            $value instanceof ClassConstFetch
            && $value->name instanceof Identifier
            && $value->name->toString() === 'class'
            && $value->class instanceof Name
        ) {
            return $this->isClassName($value->class, $className);
        }

        if ($value instanceof String_) {
            return mb_ltrim($value->value, '\\') === $className;
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
            return mb_ltrim($resolvedName->toString(), '\\') === $className;
        }

        return mb_ltrim($name->toString(), '\\') === $className
            || mb_substr($className, mb_strrpos($className, '\\') + 1) === $name->toString();
    }

    private function isControllerClassName(string $className): bool
    {
        return in_array(
            mb_ltrim($className, '\\'),
            ['yii\base\Controller', 'yii\web\Controller', 'yii\rest\Controller'],
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

        if ($class->getName() === $parentClass->getName()) {
            return true;
        }

        return $class->isSubclassOfClass($parentClass);
    }
}
