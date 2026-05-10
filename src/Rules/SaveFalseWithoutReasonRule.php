<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final readonly class SaveFalseWithoutReasonRule implements Rule
{
    /**
     * @param list<string> $allowedNamespaces
     */
    public function __construct(
        private array $allowedNamespaces = [],
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if ($node->name->toString() !== 'save') {
            return [];
        }

        if ($node->args === []) {
            return [];
        }

        if (!$this->isFalseLiteral($node->args[0]->value)) {
            return [];
        }

        if ($this->isAllowedNamespace($scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Do not call save(false) without explicit validation bypass reason.')
                ->identifier('yii.saveFalseWithoutReason')
                ->build(),
        ];
    }

    private function isFalseLiteral(Node\Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && strtolower($expr->name->toString()) === 'false';
    }

    private function isAllowedNamespace(Scope $scope): bool
    {
        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return false;
        }

        foreach ($this->allowedNamespaces as $allowedNamespace) {
            if ($namespace === $allowedNamespace || str_starts_with($namespace, $allowedNamespace . '\\')) {
                return true;
            }
        }

        return false;
    }
}
