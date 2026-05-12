<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiClassHierarchy;
use Vix\PhpstanYiiPolicyRules\Support\YiiMethod;

/**
 * @implements Rule<Class_>
 */
final readonly class ControllerBeforeActionParentResultIgnoredRule implements Rule
{
    private const string METHOD_NAME = 'beforeAction';

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

        if (!$this->isControllerOrAction($node, $scope)) {
            return [];
        }

        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() !== self::METHOD_NAME) {
                continue;
            }

            $yiiMethod = new YiiMethod($method);

            if (!$yiiMethod->hasIgnoredParentCallResult(self::METHOD_NAME)) {
                return [];
            }

            return [
                RuleErrorBuilder::message('Controller beforeAction() must use parent::beforeAction() result.')
                    ->identifier('yii.controllerBeforeActionParentResultIgnored')
                    ->line($method->getStartLine())
                    ->build(),
            ];
        }

        return [];
    }

    private function isControllerOrAction(Class_ $class, Scope $scope): bool
    {
        return $this->classHierarchy->isSubclassOfAny(
            $class,
            $scope,
            ['yii\base\Controller', 'yii\web\Controller', 'yii\rest\Controller', 'yii\base\Action'],
        );
    }
}
