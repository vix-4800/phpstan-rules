<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\AstNameResolver;
use Vix\PhpstanRules\Support\YiiRuleArrayInspector;

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
     * @param Class_ $node
     * @param Scope  $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isModelClass($node, $scope)) {
            return [];
        }

        $errors = [];

        foreach (YiiRuleArrayInspector::findRulesMethod($node)->stmts ?? [] as $statement) {
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
        $validator = YiiRuleArrayInspector::validatorExpr($rule);

        if ($validator instanceof String_) {
            return $validator->value === 'image';
        }

        return $validator !== null
            && AstNameResolver::classConstFetchMatches($validator, self::IMAGE_VALIDATOR_CLASS);
    }

    private function hasConstraint(Array_ $rule): bool
    {
        return YiiRuleArrayInspector::hasAnyKey($rule, self::CONSTRAINT_KEYS);
    }
}
