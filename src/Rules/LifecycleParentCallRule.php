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
use Vix\PhpstanYiiPolicyRules\Support\YiiActiveRecordFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class LifecycleParentCallRule implements Rule
{
    private const array REQUIRED_PARENT_CALL_METHODS = [
        'beforeValidate',
        'beforeSave',
        'afterSave',
        'afterFind',
        'afterDelete',
    ];

    private YiiActiveRecordFactory $activeRecordFactory;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->activeRecordFactory = new YiiActiveRecordFactory($reflectionProvider);
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

        $activeRecord = $this->activeRecordFactory->getActiveRecord($node, $scope);

        if ($activeRecord === null) {
            return [];
        }

        $errors = [];

        foreach (self::REQUIRED_PARENT_CALL_METHODS as $methodName) {
            $method = $activeRecord->lifecycleMethod($methodName);

            if ($method === null || $method->hasParentCall($methodName)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'ActiveRecord lifecycle method \'%s()\' must call parent::%s().',
                $methodName,
                $methodName,
            ))
                ->identifier('yii.lifecycleParentCall')
                ->line($method->line())
                ->build();
        }

        return $errors;
    }
}
