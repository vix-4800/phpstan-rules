<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiInitClassFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class ComponentInitParentCallRule implements Rule
{
    private YiiInitClassFactory $initClassFactory;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->initClassFactory = new YiiInitClassFactory($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Class_) {
            return [];
        }

        $initClass = $this->initClassFactory->getInitClass($node, $scope);

        if ($initClass === null || !$initClass->hasInitMethod()) {
            return [];
        }

        $initMethod = $initClass->initMethod();

        if ($initMethod === null || $initMethod->hasParentCall('init')) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Yii init() override must call parent::init().')
                ->identifier('yii.componentInitParentCall')
                ->line($initMethod->line())
                ->build(),
        ];
    }
}
