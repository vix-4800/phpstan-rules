<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final readonly class YiiControllerFactory
{
    private YiiClassHierarchy $classHierarchy;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->classHierarchy = new YiiClassHierarchy($reflectionProvider);
    }

    public function getController(Class_ $class, Scope $scope): ?YiiController
    {
        if (!$this->isYiiController($class, $scope)) {
            return null;
        }

        return new YiiController(
            $class,
            $this->getActions($class),
            $this->getExternalActionIds($class),
            $this->getBehaviors($class),
            $this,
        );
    }

    /**
     * @param Class_ $class
     *
     * @return list<YiiControllerAction>
     */
    private function getActions(Class_ $class): array
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

            $actions[] = new YiiControllerAction($method, $this->getActionId($method));
        }

        return $actions;
    }

    private function getActionId(ClassMethod $action): string
    {
        $actionName = mb_substr($action->name->toString(), mb_strlen('action'));
        $actionId = preg_replace('/(?<!^)[A-Z]/', '-$0', $actionName);

        return mb_strtolower($actionId ?? $actionName);
    }

    /**
     * @param Class_ $class
     *
     * @return list<YiiControllerBehavior>
     */
    private function getBehaviors(Class_ $class): array
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'behaviors') {
                continue;
            }

            return $this->getMethodBehaviors($method);
        }

        return [];
    }

    /**
     * @param ClassMethod $method
     *
     * @return list<YiiControllerBehavior>
     */
    private function getMethodBehaviors(ClassMethod $method): array
    {
        $behaviors = [];

        foreach ($this->getReturnedArrayItems($method) as $item) {
            if (!$item->value instanceof Array_) {
                continue;
            }

            $behaviors[] = new YiiControllerBehavior($item->value);
        }

        return $behaviors;
    }

    /**
     * @param Class_ $class
     *
     * @return list<string>
     */
    private function getExternalActionIds(Class_ $class): array
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'actions') {
                continue;
            }

            return $this->getMethodActionIds($method);
        }

        return [];
    }

    /**
     * @param ClassMethod $method
     *
     * @return list<string>
     */
    private function getMethodActionIds(ClassMethod $method): array
    {
        $actionIds = [];

        foreach ($this->getReturnedArrayItems($method) as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            $actionIds[] = $item->key->value;
        }

        return array_values(array_unique($actionIds));
    }

    /**
     * @param ClassMethod $method
     *
     * @return list<ArrayItem>
     */
    private function getReturnedArrayItems(ClassMethod $method): array
    {
        $finder = new NodeFinder();
        $items = [];

        foreach ($finder->findInstanceOf($method->stmts ?? [], Return_::class) as $return) {
            if (!$return->expr instanceof Array_) {
                continue;
            }

            foreach ($return->expr->items as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function behaviorIsClass(YiiControllerBehavior $behavior, string $behaviorClassName): bool
    {
        $class = $behavior->arrayItem('class');

        return $class instanceof Expr && $this->isClassNameValue($class, $behaviorClassName);
    }

    private function isYiiController(Class_ $class, Scope $scope): bool
    {
        return $this->classHierarchy->isSubclassOfAny(
            $class,
            $scope,
            ['yii\base\Controller', 'yii\web\Controller', 'yii\rest\Controller'],
        );
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
}
