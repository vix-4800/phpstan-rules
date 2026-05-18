<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiController;
use Vix\PhpstanRules\Support\YiiControllerBehavior;
use Vix\PhpstanRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class UnknownActionInBehaviorRule implements Rule
{
    private const string ACCESS_CONTROL = 'yii\filters\AccessControl';

    private const string AJAX_FILTER = 'yii\filters\AjaxFilter';

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

        $errors = [];

        foreach ($controller->behaviors() as $behavior) {
            array_push(
                $errors,
                ...$this->validateBehaviorActionList($controller, $behavior, 'only'),
                ...$this->validateBehaviorActionList($controller, $behavior, 'except'),
                ...$this->validateAccessRuleActions($controller, $behavior),
                ...$this->validateVerbFilterActions($controller, $behavior),
            );
        }

        return $errors;
    }

    /**
     * @param YiiController         $controller
     * @param YiiControllerBehavior $behavior
     * @param string                $key
     *
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorActionList(
        YiiController $controller,
        YiiControllerBehavior $behavior,
        string $key,
    ): array {
        $value = $behavior->arrayItem($key);

        if (!$value instanceof Array_) {
            return [];
        }

        return $this->validateStringActionList($controller, $value, $this->behaviorSource($behavior, $key));
    }

    /**
     * @param YiiController         $controller
     * @param YiiControllerBehavior $behavior
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAccessRuleActions(YiiController $controller, YiiControllerBehavior $behavior): array
    {
        if (!$this->controllerFactory->behaviorIsClass($behavior, self::ACCESS_CONTROL)) {
            return [];
        }

        $rules = $behavior->arrayItem('rules');

        if (!$rules instanceof Array_) {
            return [];
        }

        $errors = [];

        foreach ($rules->items as $item) {
            if (!$item->value instanceof Array_) {
                continue;
            }

            $actions = new YiiControllerBehavior($item->value)->arrayItem('actions');

            if (!$actions instanceof Array_) {
                continue;
            }

            array_push($errors, ...$this->validateStringActionList($controller, $actions, 'rules[*].actions'));
        }

        return $errors;
    }

    /**
     * @param YiiController         $controller
     * @param YiiControllerBehavior $behavior
     *
     * @return list<IdentifierRuleError>
     */
    private function validateVerbFilterActions(YiiController $controller, YiiControllerBehavior $behavior): array
    {
        if (!$this->controllerFactory->behaviorIsClass($behavior, self::VERB_FILTER)) {
            return [];
        }

        $actions = $behavior->arrayItem('actions');

        if (!$actions instanceof Array_) {
            return [];
        }

        $errors = [];

        foreach ($actions->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($controller->hasActionId($item->key->value)) {
                continue;
            }

            $errors[] = $this->buildUnknownActionError($item->key->value, 'VerbFilter::actions', $item->key->getStartLine());
        }

        return $errors;
    }

    /**
     * @param YiiController $controller
     * @param Array_        $actions
     * @param string        $source
     *
     * @return list<IdentifierRuleError>
     */
    private function validateStringActionList(YiiController $controller, Array_ $actions, string $source): array
    {
        $errors = [];

        foreach ($actions->items as $item) {
            if (!$item->value instanceof String_) {
                continue;
            }

            if ($controller->hasActionId($item->value->value)) {
                continue;
            }

            $errors[] = $this->buildUnknownActionError($item->value->value, $source, $item->value->getStartLine());
        }

        return $errors;
    }

    private function behaviorSource(YiiControllerBehavior $behavior, string $key): string
    {
        if ($key === 'only' && $this->controllerFactory->behaviorIsClass($behavior, self::AJAX_FILTER)) {
            return 'AjaxFilter::only';
        }

        return $key;
    }

    private function buildUnknownActionError(string $actionId, string $source, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Behavior references unknown controller action id \'%s\' in %s.',
            $actionId,
            $source,
        ))
            ->identifier('yii.unknownActionInBehavior')
            ->line($line)
            ->build();
    }
}
