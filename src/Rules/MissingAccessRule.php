<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

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
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
final readonly class MissingAccessRule implements Rule
{
    private const string ACCESS_CONTROL = 'yii\\filters\\AccessControl';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isYiiController($node, $scope)) {
            return [];
        }

        $actions = $this->getActionMethods($node);

        if ($actions === []) {
            return [];
        }

        $errors = [];

        foreach ($actions as $action) {
            if ($this->hasAccessControlForAction($node, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action %s() is missing AccessControl behavior.',
                $action->name->toString(),
            ))
                ->identifier('yii.missingAccessRule')
                ->line($action->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isYiiController(Class_ $class, Scope $scope): bool
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

    /**
     * @return list<ClassMethod>
     */
    private function getActionMethods(Class_ $class): array
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

    private function hasAccessControlForAction(Class_ $class, ClassMethod $action): bool
    {
        $actionId = $this->getActionId($action);

        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'behaviors') {
                continue;
            }

            return $this->containsAccessControlForAction($method, $actionId);
        }

        return false;
    }

    private function getActionId(ClassMethod $action): string
    {
        $actionName = substr($action->name->toString(), strlen('action'));
        $actionId = preg_replace('/(?<!^)[A-Z]/', '-$0', $actionName);

        return strtolower($actionId ?? $actionName);
    }

    private function containsAccessControlForAction(ClassMethod $method, string $actionId): bool
    {
        foreach ($this->getAccessControlBehaviors($method) as $behavior) {
            if (!$this->behaviorAppliesToAction($behavior, $actionId)) {
                continue;
            }

            if ($this->behaviorRulesCoverAction($behavior, $actionId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Array_>
     */
    private function getAccessControlBehaviors(ClassMethod $method): array
    {
        $finder = new NodeFinder();
        $behaviors = [];

        foreach ($finder->findInstanceOf($method->stmts ?? [], Array_::class) as $array) {
            if (!$this->isAccessControlBehavior($array)) {
                continue;
            }

            $behaviors[] = $array;
        }

        return $behaviors;
    }

    private function isAccessControlBehavior(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== 'class') {
                continue;
            }

            return $this->isAccessControlValue($item->value);
        }

        return false;
    }

    private function behaviorAppliesToAction(Array_ $behavior, string $actionId): bool
    {
        $only = $this->getStringListItem($behavior, 'only');

        if ($only !== null && !in_array($actionId, $only, true)) {
            return false;
        }

        $except = $this->getStringListItem($behavior, 'except');

        return $except === null || !in_array($actionId, $except, true);
    }

    private function behaviorRulesCoverAction(Array_ $behavior, string $actionId): bool
    {
        $rules = $this->getArrayItem($behavior, 'rules');

        if (!$rules instanceof Array_) {
            return true;
        }

        foreach ($rules->items as $item) {
            if (!$item->value instanceof Array_) {
                continue;
            }

            $actions = $this->getStringListItem($item->value, 'actions');

            if ($actions === null || in_array($actionId, $actions, true)) {
                return true;
            }
        }

        return false;
    }

    private function isAccessControlValue(Node\Expr $value): bool
    {
        if (
            $value instanceof ClassConstFetch
            && $value->name instanceof Node\Identifier
            && $value->name->toString() === 'class'
            && $value->class instanceof Name
        ) {
            return $this->isAccessControlName($value->class);
        }

        if ($value instanceof String_) {
            return ltrim($value->value, '\\') === self::ACCESS_CONTROL;
        }

        if ($value instanceof ConstFetch) {
            return $this->isAccessControlName($value->name);
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private function getStringListItem(Array_ $array, string $key): ?array
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

    private function getArrayItem(Array_ $array, string $key): ?Node\Expr
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== $key) {
                continue;
            }

            return $item->value;
        }

        return null;
    }

    private function isAccessControlName(Name $name): bool
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return ltrim($resolvedName->toString(), '\\') === self::ACCESS_CONTROL;
        }

        return ltrim($name->toString(), '\\') === self::ACCESS_CONTROL
            || $name->toString() === 'AccessControl';
    }
}
