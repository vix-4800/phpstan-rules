<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerRuleHelper;

/**
 * @implements Rule<Class_>
 */
final readonly class MissingAccessRule implements Rule
{
    private const string ACCESS_CONTROL = 'yii\filters\AccessControl';

    private YiiControllerRuleHelper $helper;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->helper = new YiiControllerRuleHelper($reflectionProvider);
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

        if (!$this->helper->isYiiController($node, $scope)) {
            return [];
        }

        $actions = $this->helper->getActionMethods($node);

        if ($actions === []) {
            return [];
        }

        $errors = [];

        foreach ($actions as $action) {
            if ($this->hasAccessControlForAction($node, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action %s() is missing AccessControl behavior.',
                $action->name->toString(),
            ))
                ->identifier('yii.missingAccessRule')
                ->line($action->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function hasAccessControlForAction(Class_ $class, ClassMethod $action): bool
    {
        $actionId = $this->helper->getActionId($action);

        foreach ($this->helper->getBehaviorsByClass($class, self::ACCESS_CONTROL) as $behavior) {
            if (!$this->helper->behaviorAppliesToAction($behavior, $actionId)) {
                continue;
            }

            if ($this->accessRulesCoverAction($behavior, $actionId)) {
                return true;
            }
        }

        return false;
    }

    private function accessRulesCoverAction(Array_ $behavior, string $actionId): bool
    {
        $rules = $this->helper->getArrayItem($behavior, 'rules');

        if (!$rules instanceof Array_) {
            return true;
        }

        foreach ($rules->items as $item) {
            if (!$item->value instanceof Array_) {
                continue;
            }

            $actions = $this->helper->getStringListItem($item->value, 'actions');

            if ($actions === null || in_array($actionId, $actions, true)) {
                return true;
            }
        }

        return false;
    }
}
