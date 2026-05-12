<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanYiiPolicyRules\Support\YiiClassHierarchy;

/**
 * @implements Rule<Class_>
 */
final readonly class SensitiveAttributeMarkedSafeRule implements Rule
{
    private const string MODEL_CLASS = 'yii\base\Model';

    private const int VALIDATOR_INDEX = 1;

    private YiiClassHierarchy $classHierarchy;

    /**
     * @param list<string> $sensitiveAttributePatterns
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        private array $sensitiveAttributePatterns,
    ) {
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

        if (!$this->classHierarchy->isSubclassOfAny($node, $scope, [self::MODEL_CLASS])) {
            return [];
        }

        $rulesMethod = $this->findRulesMethod($node);

        if ($rulesMethod === null) {
            return [];
        }

        $errors = [];

        foreach ($rulesMethod->stmts ?? [] as $statement) {
            if (!$statement instanceof Return_ || !$statement->expr instanceof Array_) {
                continue;
            }

            foreach ($statement->expr->items as $item) {
                if (!$item->value instanceof Array_) {
                    continue;
                }

                if ($this->hasScenarioRestriction($item->value) || $this->isUnsafeValidator($item->value)) {
                    continue;
                }

                foreach ($this->getSensitiveAttributes($item->value) as $attribute) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Sensitive attribute \'%s\' must not be mass assignable without scenario restriction.',
                        $attribute,
                    ))
                        ->identifier('yii.sensitiveAttributeMarkedSafe')
                        ->line($item->value->getStartLine())
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function getSensitiveAttributes(Array_ $rule): array
    {
        $attributes = [];
        $attributeExpr = $this->getAttributeExpr($rule);

        if ($attributeExpr instanceof String_) {
            $attributes[] = $attributeExpr->value;
        }

        if ($attributeExpr instanceof Array_) {
            foreach ($attributeExpr->items as $item) {
                if ($item->value instanceof String_) {
                    $attributes[] = $item->value->value;
                }
            }
        }

        return array_values(array_filter(
            $attributes,
            fn(string $attribute): bool => $this->isSensitiveAttribute($attribute),
        ));
    }

    private function getAttributeExpr(Array_ $rule): Expr
    {
        foreach ($rule->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === 'attributes') {
                return $item->value;
            }
        }

        return $rule->items[0]->value;
    }

    private function hasScenarioRestriction(Array_ $rule): bool
    {
        foreach ($rule->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if (in_array($item->key->value, ['on', 'except'], true)) {
                return true;
            }
        }

        return false;
    }

    private function isUnsafeValidator(Array_ $rule): bool
    {
        $validator = $rule->items[self::VALIDATOR_INDEX] ?? null;

        if ($validator === null) {
            return false;
        }

        if ($validator->value instanceof String_) {
            return strcasecmp($validator->value->value, 'unsafe') === 0;
        }

        return $validator->value instanceof ClassConstFetch
            && $validator->value->name instanceof Identifier
            && $validator->value->class instanceof Name
            && strcasecmp($validator->value->name->toString(), 'class') === 0
            && str_ends_with($validator->value->class->toString(), 'UnsafeValidator');
    }

    private function isSensitiveAttribute(string $attribute): bool
    {
        foreach ($this->sensitiveAttributePatterns as $pattern) {
            if (preg_match($pattern, $attribute) === 1) {
                return true;
            }
        }

        return false;
    }

    private function findRulesMethod(Class_ $class): ?ClassMethod
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === 'rules') {
                return $method;
            }
        }

        return null;
    }
}
