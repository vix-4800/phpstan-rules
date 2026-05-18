<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiController;
use Vix\PhpstanRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class NativeHeaderInControllerRule implements Rule
{
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

        return $this->findNativeHeaders($controller);
    }

    /**
     * @param YiiController $controller
     *
     * @return list<IdentifierRuleError>
     */
    private function findNativeHeaders(YiiController $controller): array
    {
        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($controller->node()->stmts, FuncCall::class) as $funcCall) {
            if (!$funcCall->name instanceof Name) {
                continue;
            }

            if (mb_strtolower($funcCall->name->toString()) !== 'header') {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                'Do not call native header() inside Yii controllers; use response component or asJson().',
            )
                ->identifier('yii.nativeHeaderInController')
                ->line($funcCall->getStartLine())
                ->build();
        }

        return $errors;
    }
}
