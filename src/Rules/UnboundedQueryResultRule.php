<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\QueryChainInspector;

/**
 * @implements Rule<MethodCall>
 */
final readonly class UnboundedQueryResultRule implements Rule
{
    private const array RESULT_METHODS = ['all', 'column'];

    private const array SAFE_TERMINAL_METHODS = ['batch', 'each', 'exists', 'count'];

    private const array BOUNDING_METHODS = ['limit', 'page', 'batch', 'each'];

    private QueryChainInspector $queryChainInspector;

    public function __construct()
    {
        $this->queryChainInspector = new QueryChainInspector();
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (in_array($node->name->toString(), self::SAFE_TERMINAL_METHODS, true)) {
            return [];
        }

        if ($this->isInDataProviderContext($node, $scope)) {
            return [];
        }

        if (!$this->queryChainInspector->isUnboundedQueryCall($node, self::RESULT_METHODS, self::BOUNDING_METHODS)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not execute unbounded query result with all() or column(); use limit(), page(), batch(), each(), exists(), count(), or a DataProvider when appropriate.')
                ->identifier('yii.unboundedQueryResult')
                ->build(),
        ];
    }

    private function isInDataProviderContext(MethodCall $node, Scope $scope): bool
    {
        $functionName = $scope->getFunctionName();

        if ($functionName !== null && str_contains(mb_strtolower($functionName), 'dataprovider')) {
            return true;
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection !== null && str_ends_with($classReflection->getName(), 'DataProvider')) {
            return true;
        }

        $parent = $node->getAttribute('parent');

        while ($parent instanceof Node) {
            if ($parent instanceof New_ && $parent->class instanceof Name) {
                return $this->isDataProviderClassName($parent->class);
            }

            $parent = $parent->getAttribute('parent');
        }

        return false;
    }

    private function isDataProviderClassName(Name $name): bool
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            $className = mb_ltrim($resolvedName->toString(), '\\');

            return str_ends_with($className, 'DataProvider');
        }

        return str_ends_with(mb_ltrim($name->toString(), '\\'), 'DataProvider');
    }
}
