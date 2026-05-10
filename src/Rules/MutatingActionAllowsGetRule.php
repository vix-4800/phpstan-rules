<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
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
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerRuleHelper;

/**
 * @implements Rule<Class_>
 */
final readonly class MutatingActionAllowsGetRule implements Rule
{
    private const string VERB_FILTER = 'yii\filters\VerbFilter';

    private const array MUTATING_METHODS = [
        'save',
        'delete',
        'deleteAll',
        'updateAll',
        'unlink',
        'push',
    ];

    private const array FILE_WRITE_FUNCTIONS = [
        'file_put_contents',
        'fopen',
        'fwrite',
    ];

    private const array MUTATING_HTTP_VERBS = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

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

        $errors = [];

        foreach ($this->helper->getActionMethods($node) as $action) {
            if (!$this->actionHasMutation($action)) {
                continue;
            }

            $verbs = $this->getConfiguredVerbs($node, $this->helper->getActionId($action));

            if ($verbs === null || $this->verbsAreSafeForMutation($verbs)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Mutating controller action %s() must not allow GET and must be restricted to POST, PUT, PATCH, or DELETE.',
                $action->name->toString(),
            ))
                ->identifier('yii.mutatingActionAllowsGet')
                ->line($action->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function actionHasMutation(ClassMethod $action): bool
    {
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($action->stmts ?? [], MethodCall::class) as $methodCall) {
            if (
                $methodCall->name instanceof Identifier
                && in_array($methodCall->name->toString(), self::MUTATING_METHODS, true)
            ) {
                return true;
            }
        }

        foreach ($finder->findInstanceOf($action->stmts ?? [], StaticCall::class) as $staticCall) {
            if (
                $staticCall->name instanceof Identifier
                && in_array($staticCall->name->toString(), self::MUTATING_METHODS, true)
            ) {
                return true;
            }
        }

        foreach ($finder->findInstanceOf($action->stmts ?? [], FuncCall::class) as $funcCall) {
            if (
                $funcCall->name instanceof Name
                && in_array(mb_strtolower($funcCall->name->toString()), self::FILE_WRITE_FUNCTIONS, true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private function getConfiguredVerbs(Class_ $class, string $actionId): ?array
    {
        foreach ($this->helper->getBehaviorsByClass($class, self::VERB_FILTER) as $behavior) {
            if (!$this->helper->behaviorAppliesToAction($behavior, $actionId)) {
                continue;
            }

            $actions = $this->helper->getArrayItem($behavior, 'actions');

            if (!$actions instanceof Array_) {
                continue;
            }

            foreach ($actions->items as $item) {
                if (!$item->key instanceof String_ || $item->key->value !== $actionId) {
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
