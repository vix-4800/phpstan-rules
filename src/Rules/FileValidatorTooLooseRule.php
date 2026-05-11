<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final readonly class FileValidatorTooLooseRule implements Rule
{
    private const string MODEL_CLASS = 'yii\base\Model';

    private const string FILE_VALIDATOR_CLASS = 'yii\validators\FileValidator';

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param Node  $node
     * @param Scope $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassMethod || $node->name->toString() !== 'rules') {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if (
            $classReflection === null
            || ($classReflection->getName() !== self::MODEL_CLASS && !$classReflection->isSubclassOf(self::MODEL_CLASS))
        ) {
            return [];
        }

        $finder = new NodeFinder();
        $errors = [];

        foreach ($finder->findInstanceOf($node->stmts ?? [], Return_::class) as $return) {
            if (!$return->expr instanceof Array_) {
                continue;
            }

            foreach ($return->expr->items as $item) {
                if ($item === null || !$item->value instanceof Array_) {
                    continue;
                }

                if (!$this->isFileValidatorRule($item->value) || $this->hasTypeConstraint($item->value)) {
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
        $validator = $rule->items[1] ?? null;

        if ($validator === null) {
            return false;
        }

        if ($validator->value instanceof String_) {
            return mb_strtolower($validator->value->value) === 'file';
        }

        if (!$validator->value instanceof ClassConstFetch || !$validator->value->name instanceof Identifier) {
            return false;
        }

        if (mb_strtolower($validator->value->name->toString()) !== 'class') {
            return false;
        }

        if (!$validator->value->class instanceof Name) {
            return false;
        }

        $className = ltrim($validator->value->class->toString(), '\\');

        return $className === self::FILE_VALIDATOR_CLASS || $className === 'FileValidator';
    }

    private function hasTypeConstraint(Array_ $rule): bool
    {
        foreach ($rule->items as $item) {
            if ($item === null || !$item->key instanceof String_) {
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
        $validator = $rule->items[1] ?? null;

        return $validator?->value->getStartLine() ?? $rule->getStartLine();
    }
}
