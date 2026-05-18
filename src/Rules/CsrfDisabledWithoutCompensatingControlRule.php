<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class CsrfDisabledWithoutCompensatingControlRule implements Rule
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

        $errors = [];

        foreach ($controller->actions() as $action) {
            array_push(
                $errors,
                ...$this->findCsrfDisableAssignments(
                    $action->method(),
                    sprintf('action \'%s\'', $action->actionName()),
                ),
            );
        }

        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() !== 'beforeAction') {
                continue;
            }

            array_push($errors, ...$this->findCsrfDisableAssignments($method, 'beforeAction()'));
        }

        return $errors;
    }

    /**
     * @param ClassMethod $method
     * @param string      $location
     *
     * @return list<IdentifierRuleError>
     */
    private function findCsrfDisableAssignments(ClassMethod $method, string $location): array
    {
        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($method->stmts ?? [], Assign::class) as $assign) {
            if (!$this->isCsrfDisableAssignment($assign)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Disabling CSRF validation in %s requires a compensating control.',
                $location,
            ))
                ->identifier('yii.csrfDisabledWithoutCompensatingControl')
                ->line($assign->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isCsrfDisableAssignment(Assign $assign): bool
    {
        if (
            !$assign->var instanceof PropertyFetch
            || !$assign->var->name instanceof Identifier
            || !$assign->var->var instanceof Variable
            || $assign->var->var->name !== 'this'
            || $assign->var->name->toString() !== 'enableCsrfValidation'
        ) {
            return false;
        }

        return $assign->expr instanceof ConstFetch
            && mb_strtolower($assign->expr->name->toString()) === 'false';
    }
}
