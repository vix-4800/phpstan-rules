<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
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

        if (!$this->queryChainInspector->isUnboundedQueryCall($node, ['all', 'column'], ['limit', 'page', 'batch', 'each'])) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not execute unbounded query result without limit(), page(), batch(), or each().')
                ->identifier('yii.unboundedQueryResult')
                ->build(),
        ];
    }
}
