<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
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
use Vix\PhpstanRules\Support\YiiController;
use Vix\PhpstanRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class ResponseFormatAssignmentInControllerRule implements Rule
{
    private const string RESPONSE_CLASS = 'yii\web\Response';

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

        return $this->findForbiddenAssignments($controller);
    }

    /**
     * @param YiiController $controller
     *
     * @return list<IdentifierRuleError>
     */
    private function findForbiddenAssignments(YiiController $controller): array
    {
        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($controller->node()->stmts, Assign::class) as $assign) {
            if (!$this->isForbiddenResponseFormatAssignment($assign)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                'Do not assign JSON/XML response formats in Yii controllers; return $this->asJson() or $this->asXml() instead.',
            )
                ->identifier('yii.responseFormatAssignmentInController')
                ->line($assign->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isForbiddenResponseFormatAssignment(Assign $assign): bool
    {
        return $this->isYiiAppResponseFormatFetch($assign->var) && $this->isJsonOrXmlResponseFormat($assign->expr);
    }

    private function isYiiAppResponseFormatFetch(Node $node): bool
    {
        if (
            !$node instanceof PropertyFetch
            || !$node->name instanceof Identifier
            || $node->name->toString() !== 'format'
            || !$node->var instanceof PropertyFetch
            || !$node->var->name instanceof Identifier
            || $node->var->name->toString() !== 'response'
            || !$node->var->var instanceof StaticPropertyFetch
            || !$node->var->var->name instanceof Identifier
            || $node->var->var->name->toString() !== 'app'
            || !$node->var->var->class instanceof Name
        ) {
            return false;
        }

        return $this->isClassName($node->var->var->class, 'Yii')
            || $this->isClassName($node->var->var->class, 'yii\BaseYii');
    }

    private function isJsonOrXmlResponseFormat(Node $node): bool
    {
        if (
            !$node instanceof ClassConstFetch
            || !$node->name instanceof Identifier
            || !$node->class instanceof Name
            || !$this->isClassName($node->class, self::RESPONSE_CLASS)
        ) {
            return false;
        }

        return in_array($node->name->toString(), ['FORMAT_JSON', 'FORMAT_XML'], true);
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
