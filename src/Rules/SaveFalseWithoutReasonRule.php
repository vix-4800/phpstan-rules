<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
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
        //
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
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->toString() !== 'save') {
            return [];
        }

        if ($node->args === []) {
            return [];
        }

        $firstArgument = $node->args[0];

        if (!$firstArgument instanceof Arg || !$this->isFalseLiteral($firstArgument->value)) {
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

    private function isFalseLiteral(Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && mb_strtolower($expr->name->toString()) === 'false';
    }

    private function isAllowedNamespace(Scope $scope): bool
    {
        $namespace = $scope->getNamespace();

        if ($namespace === null) {
            return false;
        }

        return array_any(
            $this->allowedNamespaces,
            static fn(string $allowedNamespace): bool => $namespace === $allowedNamespace || str_starts_with($namespace, $allowedNamespace . '\\'),
        );
    }
}
