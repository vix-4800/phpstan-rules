<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerRuleHelper;

/**
 * @implements Rule<Class_>
 */
final readonly class MissingVerbFilterRule implements Rule
{
    private const string VERB_FILTER = 'yii\\filters\\VerbFilter';

    private YiiControllerRuleHelper $helper;

    public function __construct(
        ReflectionProvider $reflectionProvider,
    ) {
        $this->helper = new YiiControllerRuleHelper($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->helper->isYiiController($node, $scope)) {
            return [];
        }

        $actions = $this->helper->getActionMethods($node);

        if ($actions === []) {
            return [];
        }

        $errors = [];

        foreach ($actions as $action) {
            if ($this->hasVerbFilterForAction($node, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action %s() is missing VerbFilter behavior.',
                $action->name->toString(),
            ))
                ->identifier('yii.missingVerbFilterRule')
                ->line($action->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function hasVerbFilterForAction(Class_ $class, ClassMethod $action): bool
    {
        $actionId = $this->helper->getActionId($action);

        foreach ($this->helper->getBehaviorsByClass($class, self::VERB_FILTER) as $behavior) {
            if (!$this->helper->behaviorAppliesToAction($behavior, $actionId)) {
                continue;
            }

            if ($this->verbActionsContainAction($behavior, $actionId)) {
                return true;
            }
        }

        return false;
    }

    private function verbActionsContainAction(Array_ $behavior, string $actionId): bool
    {
        $actions = $this->helper->getArrayItem($behavior, 'actions');

        if (!$actions instanceof Array_) {
            return false;
        }

        foreach ($actions->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === $actionId) {
                return true;
            }
        }

        return false;
    }
}
