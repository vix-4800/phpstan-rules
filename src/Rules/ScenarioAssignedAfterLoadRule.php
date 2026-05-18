<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<Namespace_>
 */
final readonly class ScenarioAssignedAfterLoadRule implements Rule
{
    private const array MASS_ASSIGNMENT_METHODS = ['load', 'setAttributes'];

    private const string MODEL_CLASS = 'yii\base\Model';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Namespace_::class;
    }

    /**
     * @param Namespace_ $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $namespaceName = $node->name?->toString() ?? '';
        $classes = $this->getClasses($node);
        $modelClassNames = $this->getModelClassNames($classes, $namespaceName, $scope);
        $errors = [];

        foreach ($classes as $class) {
            foreach ($class->getMethods() as $method) {
                $errors = [
                    ...$errors,
                    ...$this->processMethod($method, $modelClassNames, $namespaceName, $scope),
                ];
            }
        }

        return $errors;
    }

    /**
     * @param list<string> $modelClassNames
     *
     * @return list<IdentifierRuleError>
     */
    private function processMethod(
        ClassMethod $method,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $modelVariables = $this->getModelParameters($method, $modelClassNames, $namespaceName, $scope);

        return $this->processStatements(
            array_values($method->stmts ?? []),
            [],
            $modelVariables,
            $modelClassNames,
            $namespaceName,
            $scope,
        );
    }

    /**
     * @param list<Stmt>          $statements
     * @param array<string, bool> $loadedVariables
     * @param array<string, bool> $modelVariables
     * @param list<string>        $modelClassNames
     *
     * @return list<IdentifierRuleError>
     */
    private function processStatements(
        array $statements,
        array $loadedVariables,
        array $modelVariables,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $errors = [];

        foreach ($statements as $statement) {
            $errors = [
                ...$errors,
                ...$this->processNodeContent(
                    $statement,
                    $loadedVariables,
                    $modelVariables,
                    $modelClassNames,
                    $namespaceName,
                    $scope,
                ),
            ];
        }

        return $errors;
    }

    /**
     * @param array<string, bool> $loadedVariables
     * @param array<string, bool> $modelVariables
     * @param list<string>        $modelClassNames
     *
     * @return list<IdentifierRuleError>
     */
    private function processExpression(
        Expr $expr,
        array &$loadedVariables,
        array &$modelVariables,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $errors = [];

        if ($expr instanceof Assign) {
            if ($expr->var instanceof Variable) {
                $variableName = $this->variableName($expr->var);
                unset($loadedVariables[$variableName], $modelVariables[$variableName]);

                if (
                    $expr->expr instanceof New_ && $this->isModelInstantiation(
                        $expr->expr,
                        $modelClassNames,
                        $namespaceName,
                        $scope,
                    )
                ) {
                    $modelVariables[$variableName] = true;
                }
            }

            if ($this->isAttributesAssignment($expr) && $this->isKnownModel($expr->var, $modelVariables, $scope)) {
                $loadedVariables[$this->modelVariableName($expr->var)] = true;
            }

            if ($this->isScenarioAssignment($expr) && $this->isKnownModel($expr->var, $modelVariables, $scope)) {
                $variableName = $this->modelVariableName($expr->var);

                if (($loadedVariables[$variableName] ?? false) === true) {
                    $errors[] = $this->buildError($expr);
                }
            }

            return [
                ...$errors,
                ...$this->processExpression(
                    $expr->expr,
                    $loadedVariables,
                    $modelVariables,
                    $modelClassNames,
                    $namespaceName,
                    $scope,
                ),
            ];
        }

        if ($expr instanceof MethodCall && $this->isModelMethodCall($expr) && $this->isKnownModel($expr->var, $modelVariables, $scope)) {
            $variableName = $this->modelVariableName($expr->var);

            if ($this->isScenarioSetter($expr) && ($loadedVariables[$variableName] ?? false) === true) {
                $errors[] = $this->buildError($expr);
            }

            if ($this->isMassAssignmentCall($expr)) {
                $loadedVariables[$variableName] = true;
            }
        }

        return [
            ...$errors,
            ...$this->processNodeContent(
                $expr,
                $loadedVariables,
                $modelVariables,
                $modelClassNames,
                $namespaceName,
                $scope,
            ),
        ];
    }

    private function buildError(Node $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            'Assign model scenario before load(), setAttributes(), or attributes mass assignment.',
        )
            ->identifier('yii.scenarioAssignedAfterLoad')
            ->line($node->getStartLine())
            ->build();
    }

    private function isMassAssignmentCall(MethodCall $call): bool
    {
        return $call->name instanceof Identifier
            && in_array($call->name->toString(), self::MASS_ASSIGNMENT_METHODS, true);
    }

    private function isScenarioSetter(MethodCall $call): bool
    {
        return $call->name instanceof Identifier && $call->name->toString() === 'setScenario';
    }

    private function isModelMethodCall(MethodCall $call): bool
    {
        return $call->var instanceof Variable && is_string($call->var->name);
    }

    private function isAttributesAssignment(Assign $assign): bool
    {
        return $assign->var instanceof PropertyFetch
            && $assign->var->var instanceof Variable
            && $assign->var->name instanceof Identifier
            && $assign->var->name->toString() === 'attributes';
    }

    private function isScenarioAssignment(Assign $assign): bool
    {
        return $assign->var instanceof PropertyFetch
            && $assign->var->var instanceof Variable
            && $assign->var->name instanceof Identifier
            && $assign->var->name->toString() === 'scenario';
    }

    private function modelVariableName(Expr $expr): string
    {
        if ($expr instanceof PropertyFetch) {
            return $this->modelVariableName($expr->var);
        }

        if ($expr instanceof Variable) {
            return $this->variableName($expr);
        }

        return '';
    }

    /**
     * @param array<string, bool> $loadedVariables
     * @param array<string, bool> $modelVariables
     * @param list<string>        $modelClassNames
     *
     * @return list<IdentifierRuleError>
     */
    private function processNodeContent(
        Node $node,
        array &$loadedVariables,
        array &$modelVariables,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $errors = [];

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Expr) {
                $errors = [
                    ...$errors,
                    ...$this->processExpression(
                        $subNode,
                        $loadedVariables,
                        $modelVariables,
                        $modelClassNames,
                        $namespaceName,
                        $scope,
                    ),
                ];
            }

            if ($subNode instanceof Stmt) {
                $errors = [
                    ...$errors,
                    ...$this->processNodeContent(
                        $subNode,
                        $loadedVariables,
                        $modelVariables,
                        $modelClassNames,
                        $namespaceName,
                        $scope,
                    ),
                ];
            }

            if (!is_array($subNode)) {
                continue;
            }

            $errors = [
                ...$errors,
                ...$this->processNodeArray(
                    $subNode,
                    $loadedVariables,
                    $modelVariables,
                    $modelClassNames,
                    $namespaceName,
                    $scope,
                ),
            ];
        }

        return $errors;
    }

    /**
     * @param array<mixed>        $nodes
     * @param array<string, bool> $loadedVariables
     * @param array<string, bool> $modelVariables
     * @param list<string>        $modelClassNames
     *
     * @return list<IdentifierRuleError>
     */
    private function processNodeArray(
        array $nodes,
        array &$loadedVariables,
        array &$modelVariables,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $errors = [];

        foreach ($nodes as $node) {
            if ($node instanceof Expr) {
                $errors = [
                    ...$errors,
                    ...$this->processExpression(
                        $node,
                        $loadedVariables,
                        $modelVariables,
                        $modelClassNames,
                        $namespaceName,
                        $scope,
                    ),
                ];
            }

            if (!($node instanceof Stmt)) {
                continue;
            }

            $errors = [
                ...$errors,
                ...$this->processNodeContent(
                    $node,
                    $loadedVariables,
                    $modelVariables,
                    $modelClassNames,
                    $namespaceName,
                    $scope,
                ),
            ];
        }

        return $errors;
    }

    /**
     * @param array<string, bool> $modelVariables
     */
    private function isKnownModel(Expr $expr, array $modelVariables, Scope $scope): bool
    {
        $variableName = $this->modelVariableName($expr);

        if ($variableName !== '' && ($modelVariables[$variableName] ?? false) === true) {
            return true;
        }

        return new ObjectType(self::MODEL_CLASS)->isSuperTypeOf($scope->getType($expr))->yes();
    }

    /**
     * @return list<Class_>
     */
    private function getClasses(Namespace_ $namespace): array
    {
        return array_values(array_filter(
            $namespace->stmts,
            static fn(Node $node): bool => $node instanceof Class_,
        ));
    }

    /**
     * @param list<Class_> $classes
     *
     * @return list<string>
     */
    private function getModelClassNames(array $classes, string $namespaceName, Scope $scope): array
    {
        $modelClassNames = [];

        foreach ($classes as $class) {
            if ($class->name === null) {
                continue;
            }

            if (!$class->extends instanceof Name) {
                continue;
            }

            $className = $this->qualifyName($class->name->toString(), $namespaceName);
            $parentName = mb_ltrim($scope->resolveName($class->extends), '\\');

            if ($parentName !== self::MODEL_CLASS && !$this->isSubclassOfModel($parentName)) {
                continue;
            }

            $modelClassNames[] = $className;
        }

        return $modelClassNames;
    }

    /**
     * @param list<string> $modelClassNames
     *
     * @return array<string, bool>
     */
    private function getModelParameters(
        ClassMethod $method,
        array $modelClassNames,
        string $namespaceName,
        Scope $scope,
    ): array {
        $modelVariables = [];

        foreach ($method->params as $param) {
            if (!$param->var instanceof Variable) {
                continue;
            }

            if (!is_string($param->var->name)) {
                continue;
            }

            $type = $param->type;

            if ($type instanceof NullableType) {
                $type = $type->type;
            }

            if (!$type instanceof Name) {
                continue;
            }

            if (
                !$this->isModelClassName(mb_ltrim($scope->resolveName($type), '\\'), $modelClassNames)
                && !$this->isModelClassName($this->qualifyName($type->toString(), $namespaceName), $modelClassNames)
            ) {
                continue;
            }

            $modelVariables[$param->var->name] = true;
        }

        return $modelVariables;
    }

    /**
     * @param list<string> $modelClassNames
     */
    private function isModelInstantiation(New_ $new, array $modelClassNames, string $namespaceName, Scope $scope): bool
    {
        if (!$new->class instanceof Name) {
            return false;
        }

        $className = mb_ltrim($scope->resolveName($new->class), '\\');

        if ($this->isModelClassName($className, $modelClassNames)) {
            return true;
        }

        return $this->isModelClassName($this->qualifyName($new->class->toString(), $namespaceName), $modelClassNames);
    }

    /**
     * @param list<string> $modelClassNames
     */
    private function isModelClassName(string $className, array $modelClassNames): bool
    {
        if ($className === self::MODEL_CLASS || in_array($className, $modelClassNames, true)) {
            return true;
        }

        return $this->isSubclassOfModel($className);
    }

    private function isSubclassOfModel(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className) || !$this->reflectionProvider->hasClass(self::MODEL_CLASS)) {
            return false;
        }

        return $this->reflectionProvider
            ->getClass($className)
            ->isSubclassOfClass($this->reflectionProvider->getClass(self::MODEL_CLASS));
    }

    private function qualifyName(string $name, string $namespaceName): string
    {
        if (str_contains($name, '\\') || $namespaceName === '') {
            return mb_ltrim($name, '\\');
        }

        return $namespaceName . '\\' . $name;
    }

    private function variableName(Variable $variable): string
    {
        return is_string($variable->name) ? $variable->name : '';
    }
}
