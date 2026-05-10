<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
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

        if ($actions === [] || $this->hasAccessControl($node)) {
            return [];
        }

        $errors = [];

        foreach ($actions as $action) {
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

        return $this->isSubclassOf($className, 'yii\\base\\Controller')
            || $this->isSubclassOf($className, 'yii\\web\\Controller')
            || $this->isSubclassOf($className, 'yii\\rest\\Controller');
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

    private function hasAccessControl(Class_ $class): bool
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'behaviors') {
                continue;
            }

            return $this->containsAccessControl($method);
        }

        return false;
    }

    private function containsAccessControl(ClassMethod $method): bool
    {
        $finder = new NodeFinder();

        $accessControlNode = $finder->findFirst($method->stmts ?? [], function (Node $node): bool {
            if (
                $node instanceof ClassConstFetch
                && $node->name instanceof Node\Identifier
                && $node->name->toString() === 'class'
                && $node->class instanceof Name
            ) {
                return $this->isAccessControlName($node->class);
            }

            if ($node instanceof String_) {
                return ltrim($node->value, '\\') === self::ACCESS_CONTROL;
            }

            if ($node instanceof ConstFetch) {
                return $this->isAccessControlName($node->name);
            }

            return false;
        });

        return $accessControlNode !== null;
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
