<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Vix\PhpstanRules\Support\YiiActiveRecordFactory;

/**
 * @implements Rule<Class_>
 */
final readonly class LifecycleSelfSaveRule implements Rule
{
    private const array MUTATING_METHOD_NAMES = [
        'save',
        'update',
        'delete',
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
     * @param Class_ $node
     * @param Scope  $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $activeRecord = $this->activeRecordFactory->getActiveRecord($node, $scope);

        if ($activeRecord === null) {
            return [];
        }

        $errors = [];

        foreach ($activeRecord->lifecycleMethods() as $method) {
            if (!$method->callsAnyThisMethod(self::MUTATING_METHOD_NAMES)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'ActiveRecord lifecycle method \'%s()\' must not call $this->save(), $this->update(), or $this->delete().',
                $method->name(),
            ))
                ->identifier('yii.lifecycleSelfSave')
                ->line($method->line())
                ->build();
        }

        return $errors;
    }
}
