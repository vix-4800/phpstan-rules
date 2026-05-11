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

/**
 * @implements Rule<Class_>
 */
final readonly class ImageValidatorTooLooseRule implements Rule
{
    private const string MODEL_CLASS = 'yii\base\Model';

    private const string IMAGE_VALIDATOR_CLASS = 'yii\validators\ImageValidator';

    private const array CONSTRAINT_KEYS = [
        'extensions',
        'mimeTypes',
        'maxSize',
        'minWidth',
        'maxWidth',
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
        //
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
        if (!$node instanceof Class_ || !$this->isModelClass($node, $scope)) {
            return [];
        }

        $errors = [];

        foreach ($this->getRulesMethod($node)->stmts ?? [] as $statement) {
            if (!$statement instanceof Return_) {
                continue;
            }

            if (!$statement->expr instanceof Array_) {
                continue;
            }

            foreach ($statement->expr->items as $item) {
                if (!$item->value instanceof Array_) {
                    continue;
                }

                if (!$this->isTooLooseImageValidatorRule($item->value)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(
                    'Yii image validator rule should declare extensions, mimeTypes, maxSize, minWidth, or maxWidth.',
                )
                    ->identifier('yii.imageValidatorTooLoose')
                    ->line($item->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    private function getRulesMethod(Class_ $class): ?ClassMethod
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === 'rules') {
                return $method;
            }
        }

        return null;
    }

    private function isModelClass(Class_ $class, Scope $scope): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parentClassName = mb_ltrim($scope->resolveName($class->extends), '\\');

        if ($parentClassName === self::MODEL_CLASS) {
            return true;
        }

        return $this->reflectionProvider->hasClass($parentClassName)
            && $this->reflectionProvider->getClass($parentClassName)->isSubclassOf(self::MODEL_CLASS);
    }

    private function isTooLooseImageValidatorRule(Array_ $rule): bool
    {
        return $this->isImageValidator($rule) && !$this->hasConstraint($rule);
    }

    private function isImageValidator(Array_ $rule): bool
    {
        $validator = $this->getValidatorExpr($rule);

        if ($validator instanceof String_) {
            return $validator->value === 'image';
        }

        return $validator instanceof ClassConstFetch
            && $validator->class instanceof Name
            && $validator->name instanceof Identifier
            && $validator->name->toString() === 'class'
            && $this->isClassName($validator->class, self::IMAGE_VALIDATOR_CLASS);
    }

    private function hasConstraint(Array_ $rule): bool
    {
        foreach ($rule->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if (in_array($item->key->value, self::CONSTRAINT_KEYS, true)) {
                return true;
            }
        }

        return false;
    }

    private function getValidatorExpr(Array_ $rule): ?Expr
    {
        $position = 0;

        foreach ($rule->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === 'validator') {
                return $item->value;
            }

            if ($item->key !== null) {
                continue;
            }

            if ($position === 1) {
                return $item->value;
            }

            ++$position;
        }

        return null;
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
