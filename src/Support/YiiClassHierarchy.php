<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final readonly class YiiClassHierarchy
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
        //
    }

    /**
     * @param list<string> $parentClassNames
     */
    public function isSubclassOfAny(Class_ $class, Scope $scope, array $parentClassNames): bool
    {
        $className = $this->resolveClassName($class, $scope);

        if ($className === null) {
            return false;
        }

        if (in_array($className, $parentClassNames, true)) {
            return false;
        }

        if ($class->extends !== null) {
            $parentName = mb_ltrim($scope->resolveName($class->extends), '\\');

            if (in_array($parentName, $parentClassNames, true)) {
                return true;
            }
        }

        foreach ($parentClassNames as $parentClassName) {
            if ($this->isSubclassOf($className, $parentClassName)) {
                return true;
            }
        }

        return false;
    }

    private function resolveClassName(Class_ $class, Scope $scope): ?string
    {
        if ($class->namespacedName !== null) {
            return mb_ltrim($class->namespacedName->toString(), '\\');
        }

        if ($class->extends !== null) {
            return mb_ltrim($scope->resolveName($class->extends), '\\');
        }

        return null;
    }

    private function isSubclassOf(string $className, string $parentClassName): bool
    {
        if (!$this->reflectionProvider->hasClass($className) || !$this->reflectionProvider->hasClass($parentClassName)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $parentClassReflection = $this->reflectionProvider->getClass($parentClassName);

        if ($classReflection->getName() === $parentClassReflection->getName()) {
            return true;
        }

        return $classReflection->isSubclassOfClass($parentClassReflection);
    }
}
