<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiController;
use Vix\PhpstanRules\Support\YiiControllerAction;
use Vix\PhpstanRules\Support\YiiControllerBehavior;
use Vix\PhpstanRules\Support\YiiControllerFactory;

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
     * @param Class_ $node
     * @param Scope  $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
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

            $actions = YiiControllerBehavior::stringListFromArrayItem($item->value, 'actions');

            if ($actions === null || in_array($actionId, $actions, true)) {
                return true;
            }
        }

        return false;
    }

}
