<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\AstNameResolver;
use Vix\PhpstanRules\Support\YiiController;
use Vix\PhpstanRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class ResponseStatusCodeAssignmentInControllerRule implements Rule
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

        return $this->findStatusCodeAssignments($controller);
    }

    /**
     * @param YiiController $controller
     *
     * @return list<IdentifierRuleError>
     */
    private function findStatusCodeAssignments(YiiController $controller): array
    {
        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($controller->node()->stmts, Assign::class) as $assign) {
            if (!$this->isResponseStatusCodeAssignment($assign)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                'Do not assign Yii::$app->response->statusCode inside Yii controllers; return a response and call setStatusCode() instead.',
            )
                ->identifier('yii.responseStatusCodeAssignmentInController')
                ->line($assign->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isResponseStatusCodeAssignment(Assign $assign): bool
    {
        if (
            !$assign->var instanceof PropertyFetch
            || !$assign->var->name instanceof Identifier
            || $assign->var->name->toString() !== 'statusCode'
            || !$assign->var->var instanceof PropertyFetch
            || !$assign->var->var->name instanceof Identifier
            || $assign->var->var->name->toString() !== 'response'
            || !$assign->var->var->var instanceof StaticPropertyFetch
            || !$assign->var->var->var->name instanceof Identifier
            || $assign->var->var->var->name->toString() !== 'app'
            || !$assign->var->var->var->class instanceof Name
        ) {
            return false;
        }

        return AstNameResolver::resolveName($assign->var->var->var->class) === 'Yii';
    }
}
