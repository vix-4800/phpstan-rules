<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiClassHierarchy;

/**
 * @implements Rule<Class_>
 */
final readonly class WebControllerOnlyActionsRule implements Rule
{
    private const array ALLOWED_OVERRIDE_METHODS = [
        'actions',
        'afterAction',
        'beforeAction',
        'behaviors',
        'init',
    ];

    private YiiClassHierarchy $classHierarchy;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->classHierarchy = new YiiClassHierarchy($reflectionProvider);
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
        if (!$this->isWebController($node, $scope)) {
            return [];
        }

        $errors = [];

        foreach ($node->getMethods() as $method) {
            if ($this->isAllowedMethod($method)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Web controller method \'%s()\' is not allowed; keep only public action*() methods and standard Yii overrides.',
                $method->name->toString(),
            ))
                ->identifier('yii.webControllerOnlyActions')
                ->line($method->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isWebController(Class_ $class, Scope $scope): bool
    {
        return $this->classHierarchy->isSubclassOfAny($class, $scope, ['yii\web\Controller'])
            && !$this->classHierarchy->isSubclassOfAny($class, $scope, ['yii\rest\Controller']);
    }

    private function isAllowedMethod(ClassMethod $method): bool
    {
        $methodName = $method->name->toString();

        if (in_array($methodName, self::ALLOWED_OVERRIDE_METHODS, true)) {
            return true;
        }

        return $method->isPublic()
            && str_starts_with($methodName, 'action')
            && mb_strlen($methodName) > mb_strlen('action');
    }
}
