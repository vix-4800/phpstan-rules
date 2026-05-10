<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
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
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerRuleHelper;

/**
 * @implements Rule<Class_>
 */
final readonly class MissingAjaxFilterRule implements Rule
{
    private const string AJAX_FILTER = 'yii\filters\AjaxFilter';

    private const string RESPONSE = 'yii\web\Response';

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
        if (!$this->helper->isYiiController($node, $scope)) {
            return [];
        }

        $errors = [];

        foreach ($this->helper->getActionMethods($node) as $action) {
            if (!$this->isAjaxEndpoint($action)) {
                continue;
            }

            if ($this->hasAjaxFilterForAction($node, $action)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'AJAX controller action %s() is missing AjaxFilter behavior.',
                $action->name->toString(),
            ))
                ->identifier('yii.missingAjaxFilterRule')
                ->line($action->getStartLine())
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
        if (
            $node instanceof MethodCall
            && $node->name instanceof Identifier
            && $node->name->toString() === 'asJson'
        ) {
            return true;
        }

        return $node instanceof ClassConstFetch
            && $node->name instanceof Identifier
            && $node->name->toString() === 'FORMAT_JSON'
            && $node->class instanceof Name
            && $this->isClassName($node->class, self::RESPONSE);
    }

    private function hasAjaxFilterForAction(Class_ $class, ClassMethod $action): bool
    {
        $actionId = $this->helper->getActionId($action);

        return array_any(
            $this->helper->getBehaviorsByClass($class, self::AJAX_FILTER),
            fn(Array_ $behavior): bool => $this->helper->behaviorAppliesToAction($behavior, $actionId),
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
