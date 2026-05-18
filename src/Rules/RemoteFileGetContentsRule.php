<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;

/**
 * @implements Rule<Expr>
 */
final class RemoteFileGetContentsRule implements Rule
{
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FuncCall || !NodeHelpers::isFunctionCall($node, 'file_get_contents')) {
            return [];
        }

        $path = NodeHelpers::argAt($node->args, 0);

        if ($path === null || !$this->isRemoteString($path->value, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Remote file_get_contents() is forbidden.')
                ->identifier('vix.remoteFileGetContents')
                ->build(),
        ];
    }

    private function isRemoteString(Expr $expr, Scope $scope): bool
    {
        if (NodeHelpers::isRemoteString($expr)) {
            return true;
        }

        return array_any(
            $scope->getType($expr)->getConstantStrings(),
            fn(ConstantStringType $stringType): bool => $this->constantStringIsRemote($stringType),
        );
    }

    private function constantStringIsRemote(ConstantStringType $stringType): bool
    {
        return preg_match('#^https?://#i', $stringType->getValue()) === 1;
    }
}
