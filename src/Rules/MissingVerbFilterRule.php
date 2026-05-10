<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
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
final readonly class MissingVerbFilterRule implements Rule
{
    private const string VERB_FILTER = 'yii\filters\VerbFilter';

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
            if ($this->hasVerbFilterForAction($controller, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action %s() is missing VerbFilter behavior.',
                $action->methodName(),
            ))
                ->identifier('yii.missingVerbFilterRule')
                ->line($action->line())
                ->build();
        }

        return $errors;
    }

    private function hasVerbFilterForAction(YiiController $controller, YiiControllerAction $action): bool
    {
        foreach ($controller->behaviorsByClass(self::VERB_FILTER) as $behavior) {
            if (!$behavior->appliesToAction($action)) {
                continue;
            }

            if ($this->verbActionsContainAction($behavior, $action->id())) {
                return true;
            }
        }

        return false;
    }

    private function verbActionsContainAction(YiiControllerBehavior $behavior, string $actionId): bool
    {
        $actions = $behavior->arrayItem('actions');

        if (!$actions instanceof Array_) {
            return false;
        }

        return array_any(
            $actions->items,
            static fn(ArrayItem $item): bool => $item->key instanceof String_ && $item->key->value === $actionId,
        );
    }
}
