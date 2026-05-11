<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class MixedResponseTypesInActionRule implements Rule
{
    private const array HTML_RESPONSE_METHODS = [
        'render',
        'renderAjax',
        'renderPartial',
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
            if (!$this->actionMixesResponseTypes($action->method())) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Controller action \'%s\' mixes JSON and HTML responses; keep one response type per action.',
                $action->actionName(),
            ))
                ->identifier('yii.mixedResponseTypesInAction')
                ->line($action->line())
                ->build();
        }

        return $errors;
    }

    private function actionMixesResponseTypes(ClassMethod $action): bool
    {
        $responseKinds = $this->collectResponseKinds($action);

        return in_array('html', $responseKinds, true) && in_array('json', $responseKinds, true);
    }

    /**
     * @return list<string>
     */
    private function collectResponseKinds(ClassMethod $action): array
    {
        $finder = new NodeFinder();
        $responseKinds = [];

        foreach ($finder->findInstanceOf($action->stmts ?? [], Return_::class) as $return) {
            $responseKind = $this->detectResponseKind($return->expr);

            if ($responseKind === null) {
                continue;
            }

            $responseKinds[] = $responseKind;
        }

        return array_values(array_unique($responseKinds));
    }

    private function detectResponseKind(?Expr $expr): ?string
    {
        if (!$expr instanceof MethodCall || !$expr->name instanceof Identifier) {
            return null;
        }

        $methodName = mb_strtolower($expr->name->toString());

        if ($methodName === 'asjson') {
            return 'json';
        }

        if ($this->isHtmlResponseMethod($methodName)) {
            return 'html';
        }

        return null;
    }

    private function isHtmlResponseMethod(string $methodName): bool
    {
        return in_array(
            $methodName,
            array_map(
                static fn(string $htmlMethodName): string => mb_strtolower($htmlMethodName),
                self::HTML_RESPONSE_METHODS,
            ),
            true,
        );
    }
}
