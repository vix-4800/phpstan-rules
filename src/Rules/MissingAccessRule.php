<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiController;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerAction;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerBehavior;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class MissingAccessRule implements Rule
{
    private const string ACCESS_CONTROL = 'yii\filters\AccessControl';

    private YiiControllerFactory $controllerFactory;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->controllerFactory = new YiiControllerFactory($reflectionProvider);
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

        $controller = $this->controllerFactory->getController($node, $scope);

        if ($controller === null) {
            return [];
        }

        $actions = $controller->actions();

        if ($actions === []) {
            return [];
        }

        $errors = [];

        foreach ($actions as $action) {
            if ($this->hasAccessControlForAction($controller, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action \'%s\' is missing AccessControl behavior.',
                $action->actionName(),
            ))
                ->identifier('yii.missingAccessRule')
                ->line($action->line())
                ->build();
        }

        return $errors;
    }

    private function hasAccessControlForAction(YiiController $controller, YiiControllerAction $action): bool
    {
        foreach ($controller->behaviorsByClass(self::ACCESS_CONTROL) as $behavior) {
            if (!$behavior->appliesToAction($action)) {
                continue;
            }

            if ($this->accessRulesCoverAction($behavior, $action->id())) {
                return true;
            }
        }

        return false;
    }

    private function accessRulesCoverAction(YiiControllerBehavior $behavior, string $actionId): bool
    {
        $rules = $behavior->arrayItem('rules');

        if (!$rules instanceof Array_) {
            return true;
        }

        foreach ($rules->items as $item) {
            if (!$item->value instanceof Array_) {
                continue;
            }

            $actions = $this->getStringListItem($item->value, 'actions');

            if ($actions === null || in_array($actionId, $actions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private function getStringListItem(Array_ $array, string $key): ?array
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== $key) {
                continue;
            }

            if (!$item->value instanceof Array_) {
                return null;
            }

            $strings = [];

            foreach ($item->value->items as $valueItem) {
                if (!$valueItem->value instanceof String_) {
                    return null;
                }

                $strings[] = $valueItem->value->value;
            }

            return $strings;
        }

        return null;
    }
}
