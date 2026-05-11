<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
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
        if (!$node instanceof MethodCall) {
            return [];
        }

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

        $hasExplicitAttributes = isset($node->args[1])
            && $node->args[1] instanceof Arg
            && $node->args[1]->value instanceof Array_;

        $message = $hasExplicitAttributes
            ? 'Avoid save(false, explicit attributes); validation is bypassed for selected fields.'
            : 'Do not call save(false) without explicit validation bypass reason.';

        return [
            RuleErrorBuilder::message($message)
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

        if (
            array_any(
                $this->allowedNamespaces,
                static fn(string $allowedNamespace): bool => $namespace === $allowedNamespace || str_starts_with($namespace, $allowedNamespace . '\\'),
            )
        ) {
            return true;
        }

        $namespaceParts = array_map(
            mb_strtolower(...),
            explode('\\', $namespace),
        );

        return array_intersect($namespaceParts, ['migrations', 'tests', 'seeders', 'seeds']) !== [];
    }
}
