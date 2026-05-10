<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiController;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerAction;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class MutatingActionAllowsGetRule implements Rule
{
    private const string VERB_FILTER = 'yii\filters\VerbFilter';

    private const array MUTATING_METHODS = [
        'save',
        'delete',
        'deleteall',
        'updateall',
        'updateattributes',
        'updatecounters',
        'updateallcounters',
        'unlink',
        'push',
        'saveas',
        'rename',
    ];

    private const array CONDITIONAL_MUTATING_METHODS = [
        'insert',
        'update',
    ];

    private const array MUTATING_FUNCTIONS = [
        'file_put_contents',
        'fwrite',
        'rename',
        'unlink',
        'mkdir',
        'rmdir',
        'copy',
        'touch',
        'chmod',
        'chown',
        'chgrp',
        'symlink',
        'link',
        'move_uploaded_file',
    ];

    private const array MUTATING_HTTP_VERBS = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

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

        foreach ($controller->actions() as $action) {
            if (!$this->actionHasMutation($action->method())) {
                continue;
            }

            $verbs = $this->getConfiguredVerbs($controller, $action);

            if ($verbs === null || $this->verbsAreSafeForMutation($verbs)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Mutating controller action \'%s\' must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                $action->actionName(),
            ))
                ->identifier('yii.mutatingActionAllowsGet')
                ->line($action->line())
                ->build();
        }

        return $errors;
    }

    private function actionHasMutation(ClassMethod $action): bool
    {
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($action->stmts ?? [], MethodCall::class) as $methodCall) {
            if ($methodCall->name instanceof Identifier && $this->callIsMutating($methodCall->name, $methodCall->args)) {
                return true;
            }
        }

        foreach ($finder->findInstanceOf($action->stmts ?? [], StaticCall::class) as $staticCall) {
            if ($staticCall->name instanceof Identifier && $this->callIsMutating($staticCall->name, $staticCall->args)) {
                return true;
            }
        }

        foreach ($finder->findInstanceOf($action->stmts ?? [], FuncCall::class) as $funcCall) {
            if ($funcCall->name instanceof Name && $this->functionCallIsMutating($funcCall)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Arg> $args
     */
    private function callIsMutating(Identifier $name, array $args): bool
    {
        $methodName = mb_strtolower($name->toString());

        if (in_array($methodName, self::MUTATING_METHODS, true)) {
            return true;
        }

        if (!in_array($methodName, self::CONDITIONAL_MUTATING_METHODS, true)) {
            return false;
        }

        return $this->firstArgumentIsFalse($args);
    }

    private function functionCallIsMutating(FuncCall $funcCall): bool
    {
        $functionName = mb_strtolower($funcCall->name->toString());

        if ($functionName === 'fopen') {
            return $this->fopenCanWrite($funcCall);
        }

        return in_array($functionName, self::MUTATING_FUNCTIONS, true);
    }

    /**
     * @param list<Arg> $args
     */
    private function firstArgumentIsFalse(array $args): bool
    {
        if (!isset($args[0])) {
            return false;
        }

        return $args[0]->value instanceof ConstFetch
            && mb_strtolower($args[0]->value->name->toString()) === 'false';
    }

    private function fopenCanWrite(FuncCall $funcCall): bool
    {
        if (!isset($funcCall->args[1])) {
            return true;
        }

        if (!$funcCall->args[1]->value instanceof String_) {
            return true;
        }

        return preg_match('/[waxc+]/i', $funcCall->args[1]->value->value) === 1;
    }

    /**
     * @return list<string>|null
     */
    private function getConfiguredVerbs(YiiController $controller, YiiControllerAction $action): ?array
    {
        foreach ($controller->behaviorsByClass(self::VERB_FILTER) as $behavior) {
            if (!$behavior->appliesToAction($action)) {
                continue;
            }

            $actions = $behavior->arrayItem('actions');

            if (!$actions instanceof Array_) {
                continue;
            }

            foreach ($actions->items as $item) {
                if (!$item->key instanceof String_ || $item->key->value !== $action->id()) {
                    continue;
                }

                if (!$item->value instanceof Array_) {
                    return null;
                }

                return $this->getVerbList($item->value);
            }
        }

        return null;
    }

    /**
     * @return list<string>|null
     */
    private function getVerbList(Array_ $verbs): ?array
    {
        $values = [];

        foreach ($verbs->items as $item) {
            if (!$item->value instanceof String_) {
                return null;
            }

            $values[] = mb_strtoupper($item->value->value);
        }

        return $values;
    }

    /**
     * @param list<string> $verbs
     */
    private function verbsAreSafeForMutation(array $verbs): bool
    {
        if (array_intersect($verbs, ['GET', 'HEAD']) !== []) {
            return false;
        }

        return array_intersect($verbs, self::MUTATING_HTTP_VERBS) !== [];
    }
}
