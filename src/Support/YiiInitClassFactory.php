<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final readonly class YiiInitClassFactory
{
    private const array INIT_CLASS_NAMES = [
        'yii\base\Component',
        'yii\base\Widget',
        'yii\base\Behavior',
        'yii\web\AssetBundle',
    ];

    private YiiClassHierarchy $classHierarchy;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->classHierarchy = new YiiClassHierarchy($reflectionProvider);
    }

    public function getInitClass(Class_ $class, Scope $scope): ?YiiInitClass
    {
        if (!$this->classHierarchy->isSubclassOfAny($class, $scope, self::INIT_CLASS_NAMES)) {
            return null;
        }

        return new YiiInitClass($class, $this->getInitMethod($class));
    }

    private function getInitMethod(Class_ $class): ?YiiMethod
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() !== 'init') {
                continue;
            }

            return new YiiMethod($method);
        }

        return null;
    }
}
