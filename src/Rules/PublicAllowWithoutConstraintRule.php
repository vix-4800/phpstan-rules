<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiController;
use Vix\PhpstanYiiPolicyRules\Support\YiiControllerFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class PublicAllowWithoutConstraintRule implements Rule
{
    private const string ACCESS_CONTROL = 'yii\filters\AccessControl';

    private const array CONSTRAINT_KEYS = [
        'roles',
        'permissions',
        'matchCallback',
        'ips',
        'verbs',
        'actions',
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

        return $this->findPublicAllowWithoutConstraint($controller);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function findPublicAllowWithoutConstraint(YiiController $controller): array
    {
        $errors = [];

        foreach ($controller->behaviorsByClass(self::ACCESS_CONTROL) as $behavior) {
            $rules = $behavior->arrayItem('rules');

            if (!$rules instanceof Array_) {
                continue;
            }

            foreach ($rules->items as $item) {
                if (!$item->value instanceof Array_) {
                    continue;
                }

                if (!$this->isPublicAllowWithoutConstraint($item->value)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(
                    'AccessControl rule allows public access without roles, permissions, matchCallback, ips, verbs, or actions.',
                )
                    ->identifier('yii.publicAllowWithoutConstraint')
                    ->line($item->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    private function isPublicAllowWithoutConstraint(Array_ $rule): bool
    {
        $allow = $this->getArrayItem($rule, 'allow');

        if (!$this->isTrueLiteral($allow)) {
            return false;
        }

        foreach ($rule->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if (in_array($item->key->value, self::CONSTRAINT_KEYS, true)) {
                return false;
            }
        }

        return true;
    }

    private function isTrueLiteral(?Expr $expr): bool
    {
        return $expr instanceof ConstFetch
            && mb_strtolower($expr->name->toString()) === 'true';
    }

    private function getArrayItem(Array_ $array, string $key): ?Expr
    {
        foreach ($array->items as $item) {
            if (!$item->key instanceof String_ || $item->key->value !== $key) {
                continue;
            }

            return $item->value;
        }

        return null;
    }
}
