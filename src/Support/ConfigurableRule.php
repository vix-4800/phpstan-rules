<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

/**
 * @implements Rule<Node>
 */
final readonly class ConfigurableRule implements Rule
{
    /**
     * @param Rule<Node>          $rule
     * @param array<string, bool> $enabledRules
     */
    public function __construct(
        private Rule $rule,
        private string $ruleName,
        private bool $allRules,
        private array $enabledRules,
    ) {
        //
    }

    public function getNodeType(): string
    {
        return $this->rule->getNodeType();
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->allRules && ($this->enabledRules[$this->ruleName] ?? false) !== true) {
            return [];
        }

        return $this->rule->processNode($node, $scope);
    }
}
