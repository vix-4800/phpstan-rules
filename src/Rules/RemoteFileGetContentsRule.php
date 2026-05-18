<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!NodeHelpers::isFunctionCall($node, 'file_get_contents') || !isset($node->args[0])) {
            return [];
        }

        if (!NodeHelpers::isRemoteString($node->args[0]->value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Remote file_get_contents() is forbidden.')
                ->identifier('vix.remoteFileGetContents')
                ->build(),
        ];
    }
}
