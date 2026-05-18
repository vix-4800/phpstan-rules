<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Support;

use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final readonly class YiiActiveRecordFactory
{
    private const array ACTIVE_RECORD_CLASSES = [
        'yii\db\BaseActiveRecord',
        'yii\db\ActiveRecord',
    ];

    private const array LIFECYCLE_METHOD_NAMES = [
        'beforeValidate',
        'afterValidate',
        'beforeSave',
        'afterSave',
        'afterFind',
        'beforeDelete',
        'afterDelete',
    ];

    private YiiClassHierarchy $classHierarchy;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->classHierarchy = new YiiClassHierarchy($reflectionProvider);
    }

    public function getActiveRecord(Class_ $class, Scope $scope): ?YiiActiveRecord
    {
        if (!$this->classHierarchy->isSubclassOfAny($class, $scope, self::ACTIVE_RECORD_CLASSES)) {
            return null;
        }

        return new YiiActiveRecord($class, $this->getLifecycleMethods($class));
    }

    /**
     * @param Class_ $class
     *
     * @return list<YiiMethod>
     */
    private function getLifecycleMethods(Class_ $class): array
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if (!in_array($method->name->toString(), self::LIFECYCLE_METHOD_NAMES, strict: true)) {
                continue;
            }

            $methods[] = new YiiMethod($method);
        }

        return $methods;
    }
}
