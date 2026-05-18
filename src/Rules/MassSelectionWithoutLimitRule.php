<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\QueryChainInspector;

/**
 * @implements Rule<MethodCall>
 */
final readonly class MassSelectionWithoutLimitRule implements Rule
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
     * @param MethodCall $node
     * @param Scope      $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isUnboundedFindAll($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not call find()->all() without limit().')
                ->identifier('yii.massSelectionWithoutLimit')
                ->build(),
        ];
    }

    private function isUnboundedFindAll(MethodCall $methodCall): bool
    {
        if (!$methodCall->name instanceof Identifier || $methodCall->name->toString() !== 'all') {
            return false;
        }

        return $this->queryChainInspector->isUnboundedQueryCall($methodCall, ['all']);
    }
}
