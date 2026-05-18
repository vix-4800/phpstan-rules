<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\QueryChainInspector;

/**
 * @implements Rule<MethodCall>
 */
final readonly class QueryOneWithoutLimitRule implements Rule
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

        if (!$this->queryChainInspector->isUnboundedQueryCall($node, ['one'])) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Call limit(1) before one().')
                ->identifier('yii.queryOneWithoutLimit')
                ->build(),
        ];
    }
}
