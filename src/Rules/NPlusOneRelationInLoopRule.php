<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Namespace_>
 */
final readonly class NPlusOneRelationInLoopRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Namespace_::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Namespace_) {
            return [];
        }

        $namespaceName = $node->name?->toString() ?? '';
        $classes = $this->getClasses($node);
        $relationsByClass = $this->getRelationsByClass($classes, $namespaceName);
        $errors = [];

        foreach ($classes as $class) {
            foreach ($class->getMethods() as $method) {
                $errors = [
                    ...$errors,
                    ...$this->processMethod($method, $relationsByClass, $namespaceName),
                ];
            }
        }

        return $errors;
    }

    /**
     * @param array<string, list<string>> $relationsByClass
     *
     * @return list<IdentifierRuleError>
     */
    private function processMethod(ClassMethod $method, array $relationsByClass, string $namespaceName): array
    {
        $queryResults = $this->getQueryResultAssignments($method, $namespaceName);
        $errors = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($method->stmts ?? [], Foreach_::class) as $foreach) {
            if (!$foreach->expr instanceof Variable || !$foreach->valueVar instanceof Variable) {
                continue;
            }

            $collectionName = $this->variableName($foreach->expr);
            $itemName = $this->variableName($foreach->valueVar);

            if ($collectionName === '' || $itemName === '') {
                continue;
            }

            $queryResult = $queryResults[$collectionName] ?? null;

            if ($queryResult === null) {
                continue;
            }

            foreach ($this->getRelationFetches($foreach, $itemName) as $relationFetch) {
                if (!$relationFetch->name instanceof Identifier) {
                    continue;
                }

                $relationName = $relationFetch->name->toString();

                if ($this->isEagerLoaded($relationName, $queryResult['eagerRelations'])) {
                    continue;
                }

                if (!in_array($relationName, $this->getRelations($queryResult['modelClass'], $relationsByClass), true)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Relation \'%s\' is read in loop without with() or joinWith(); eager load it to avoid N+1 queries.',
                    $relationName,
                ))
                    ->identifier('yii.nPlusOneRelationInLoop')
                    ->line($relationFetch->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /**
     * @param array<string, list<string>> $relationsByClass
     *
     * @return list<string>
     */
    private function getRelations(string $modelClass, array $relationsByClass): array
    {
        $relations = $relationsByClass[$modelClass] ?? [];

        if (!$this->reflectionProvider->hasClass($modelClass)) {
            return $relations;
        }

        foreach ($this->reflectionProvider->getClass($modelClass)->getNativeReflection()->getMethods() as $method) {
            $relationName = $this->getNativeActiveQueryRelationName($method);

            if ($relationName === null) {
                continue;
            }

            $relations[] = $relationName;
        }

        return array_values(array_unique($relations));
    }

    private function getNativeActiveQueryRelationName(\ReflectionMethod $method): ?string
    {
        $methodName = $method->getName();

        if (!str_starts_with($methodName, 'get') || mb_strlen($methodName) === mb_strlen('get')) {
            return null;
        }

        $returnType = $method->getReturnType();

        if (!$returnType instanceof \ReflectionNamedType || $returnType->isBuiltin()) {
            return null;
        }

        $returnTypeName = mb_ltrim($returnType->getName(), '\\');

        if ($returnTypeName !== 'yii\db\ActiveQuery' && !str_ends_with($returnTypeName, '\ActiveQuery')) {
            return null;
        }

        return lcfirst(mb_substr($methodName, mb_strlen('get')));
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
     * @return array<string, list<string>>
     */
    private function getRelationsByClass(array $classes, string $namespaceName): array
    {
        $relationsByClass = [];

        foreach ($classes as $class) {
            if ($class->name === null) {
                continue;
            }

            $className = $this->qualifyName($class->name->toString(), $namespaceName);

            foreach ($class->getMethods() as $method) {
                $relationName = $this->getActiveQueryRelationName($method);

                if ($relationName === null) {
                    continue;
                }

                $relationsByClass[$className][] = $relationName;
            }
        }

        return $relationsByClass;
    }

    private function getActiveQueryRelationName(ClassMethod $method): ?string
    {
        $methodName = $method->name->toString();

        if (!str_starts_with($methodName, 'get') || mb_strlen($methodName) === mb_strlen('get')) {
            return null;
        }

        if (!$this->isActiveQueryReturnType($method->returnType)) {
            return null;
        }

        return lcfirst(mb_substr($methodName, mb_strlen('get')));
    }

    private function isActiveQueryReturnType(Node|null $returnType): bool
    {
        if ($returnType instanceof NullableType) {
            $returnType = $returnType->type;
        }

        if (!$returnType instanceof Name) {
            return false;
        }

        $className = mb_ltrim($returnType->toString(), '\\');
        $resolvedName = $returnType->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            $className = mb_ltrim($resolvedName->toString(), '\\');
        }

        return $className === 'yii\db\ActiveQuery' || str_ends_with($className, '\ActiveQuery');
    }

    /**
     * @return array<string, array{modelClass: string, eagerRelations: list<string>}>
     */
    private function getQueryResultAssignments(ClassMethod $method, string $namespaceName): array
    {
        $queryResults = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($method->stmts ?? [], Assign::class) as $assign) {
            if (!$assign->var instanceof Variable) {
                continue;
            }

            $variableName = $this->variableName($assign->var);

            if ($variableName === '') {
                continue;
            }

            $queryResult = $this->getQueryResult($assign->expr, $namespaceName);

            if ($queryResult === null) {
                continue;
            }

            $queryResults[$variableName] = $queryResult;
        }

        return $queryResults;
    }

    /**
     * @return array{modelClass: string, eagerRelations: list<string>}|null
     */
    private function getQueryResult(Expr $expr, string $namespaceName): ?array
    {
        if (!$expr instanceof MethodCall || !$expr->name instanceof Identifier || $expr->name->toString() !== 'all') {
            return null;
        }

        $source = $this->getQuerySource($expr);

        if (!$source instanceof StaticCall || !$source->name instanceof Identifier || $source->name->toString() !== 'find') {
            return null;
        }

        if (!$source->class instanceof Name) {
            return null;
        }

        return [
            'modelClass' => $this->resolveClassName($source->class, $namespaceName),
            'eagerRelations' => $this->getEagerRelations($this->getMethodChain($expr)),
        ];
    }

    /**
     * @return list<MethodCall>
     */
    private function getMethodChain(MethodCall $terminalCall): array
    {
        $chain = [];
        $expr = $terminalCall;

        while ($expr instanceof MethodCall) {
            $chain[] = $expr;
            $expr = $expr->var;
        }

        return $chain;
    }

    private function getQuerySource(MethodCall $terminalCall): Expr
    {
        $expr = $terminalCall->var;

        while ($expr instanceof MethodCall) {
            $expr = $expr->var;
        }

        return $expr;
    }

    /**
     * @param list<MethodCall> $chain
     *
     * @return list<string>
     */
    private function getEagerRelations(array $chain): array
    {
        $relations = [];

        foreach ($chain as $call) {
            if (!$call->name instanceof Identifier || !in_array($call->name->toString(), ['with', 'joinWith'], true)) {
                continue;
            }

            foreach ($call->args as $arg) {
                if (!$arg instanceof Arg) {
                    continue;
                }

                $relations = [
                    ...$relations,
                    ...$this->getRelationNames($arg->value),
                ];
            }
        }

        return array_values(array_unique($relations));
    }

    /**
     * @return list<string>
     */
    private function getRelationNames(Expr $expr): array
    {
        if ($expr instanceof String_) {
            return [$this->rootRelationName($expr->value)];
        }

        if (!$expr instanceof Array_) {
            return [];
        }

        $relations = [];

        foreach ($expr->items as $item) {
            if ($item->key instanceof String_) {
                $relations[] = $this->rootRelationName($item->key->value);
            }

            if ($item->value instanceof String_) {
                $relations[] = $this->rootRelationName($item->value->value);
            }
        }

        return $relations;
    }

    /**
     * @return list<PropertyFetch>
     */
    private function getRelationFetches(Foreach_ $foreach, string $itemName): array
    {
        $fetches = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($foreach->stmts, PropertyFetch::class) as $propertyFetch) {
            if (!$propertyFetch->var instanceof Variable || !$propertyFetch->name instanceof Identifier) {
                continue;
            }

            if ($this->variableName($propertyFetch->var) !== $itemName) {
                continue;
            }

            $fetches[] = $propertyFetch;
        }

        return $fetches;
    }

    /**
     * @param list<string> $eagerRelations
     */
    private function isEagerLoaded(string $relationName, array $eagerRelations): bool
    {
        return in_array($relationName, $eagerRelations, true);
    }

    private function resolveClassName(Name $name, string $namespaceName): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return mb_ltrim($resolvedName->toString(), '\\');
        }

        return $this->qualifyName($name->toString(), $namespaceName);
    }

    private function qualifyName(string $name, string $namespaceName): string
    {
        if (str_contains($name, '\\') || $namespaceName === '') {
            return mb_ltrim($name, '\\');
        }

        return $namespaceName . '\\' . $name;
    }

    private function rootRelationName(string $relationName): string
    {
        $dotPosition = mb_strpos($relationName, '.');

        if ($dotPosition === false) {
            return $relationName;
        }

        return mb_substr($relationName, 0, $dotPosition);
    }

    private function variableName(Variable $variable): string
    {
        return is_string($variable->name) ? $variable->name : '';
    }
}
