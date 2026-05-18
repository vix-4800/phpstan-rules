<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiClassHierarchy;

/**
 * @implements Rule<Class_>
 */
final readonly class FileValidatorTooLooseRule implements Rule
{
    private const string MODEL_CLASS = 'yii\base\Model';

    private const string FILE_VALIDATOR_CLASS = 'yii\validators\FileValidator';

    private const int VALIDATOR_INDEX = 1;

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
        if (!$this->classHierarchy->isSubclassOfAny($node, $scope, [self::MODEL_CLASS])) {
            return [];
        }

        $rulesMethod = $this->findRulesMethod($node);

        if ($rulesMethod === null) {
            return [];
        }

        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($rulesMethod->stmts ?? [], Return_::class) as $return) {
            if (!$return->expr instanceof Array_) {
                continue;
            }

            foreach ($return->expr->items as $item) {
                if (!$item->value instanceof Array_) {
                    continue;
                }

                if (!$this->isFileValidatorRule($item->value)) {
                    continue;
                }

                if ($this->hasTypeConstraint($item->value)) {
                    continue;
                }

                $errors[] = RuleErrorBuilder::message(
                    'Yii file validator should declare at least one of \'extensions\' or \'mimeTypes\'; consider \'maxSize\' too.',
                )
                    ->identifier('yii.fileValidatorTooLoose')
                    ->line($this->validatorLine($item->value))
                    ->build();
            }
        }

        return $errors;
    }

    private function isFileValidatorRule(Array_ $rule): bool
    {
        $validator = $rule->items[self::VALIDATOR_INDEX] ?? null;

        if ($validator === null) {
            return false;
        }

        if ($validator->value instanceof String_) {
            return strcasecmp($validator->value->value, 'file') === 0;
        }

        if (!$validator->value instanceof ClassConstFetch || !$validator->value->name instanceof Identifier) {
            return false;
        }

        if (strcasecmp($validator->value->name->toString(), 'class') !== 0) {
            return false;
        }

        if (!$validator->value->class instanceof Name) {
            return false;
        }

        $className = mb_ltrim($validator->value->class->toString(), '\\');

        return in_array($className, [self::FILE_VALIDATOR_CLASS, 'FileValidator'], true);
    }

    private function hasTypeConstraint(Array_ $rule): bool
    {
        foreach ($rule->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if (in_array($item->key->value, ['extensions', 'mimeTypes'], true)) {
                return true;
            }
        }

        return false;
    }

    private function validatorLine(Array_ $rule): int
    {
        $validator = $rule->items[self::VALIDATOR_INDEX] ?? null;

        return $validator?->value->getStartLine() ?? $rule->getStartLine();
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
