<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
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
final readonly class MissingAjaxFilterRule implements Rule
{
    private const string AJAX_FILTER = 'yii\filters\AjaxFilter';

    private const string RESPONSE = 'yii\web\Response';

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
            if (!$this->isAjaxEndpoint($action->method())) {
                continue;
            }

            if ($this->hasAjaxFilterForAction($controller, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'AJAX controller action \'%s\' is missing AjaxFilter behavior.',
                $action->actionName(),
            ))
                ->identifier('yii.missingAjaxFilterRule')
                ->line($action->line())
                ->build();
        }

        return $errors;
    }

    private function isAjaxEndpoint(ClassMethod $action): bool
    {
        $finder = new NodeFinder();

        return $finder->findFirst(
            $action->stmts ?? [],
            $this->isAjaxEndpointNode(...),
        ) instanceof Node;
    }

    private function isAjaxEndpointNode(Node $node): bool
    {
        if ($node instanceof MethodCall && $node->name instanceof Identifier && $node->name->toString() === 'asJson') {
            return true;
        }

        return $node instanceof ClassConstFetch
            && $node->name instanceof Identifier
            && $node->name->toString() === 'FORMAT_JSON'
            && $node->class instanceof Name
            && $this->isClassName($node->class, self::RESPONSE);
    }

    private function hasAjaxFilterForAction(YiiController $controller, YiiControllerAction $action): bool
    {
        return array_any(
            $controller->behaviorsByClass(self::AJAX_FILTER),
            static fn(YiiControllerBehavior $behavior): bool => $behavior->appliesToAction($action),
        );
    }

    private function isClassName(Name $name, string $className): bool
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return mb_ltrim($resolvedName->toString(), '\\') === $className;
        }

        return mb_ltrim($name->toString(), '\\') === $className
            || mb_substr($className, mb_strrpos($className, '\\') + 1) === $name->toString();
    }
}
