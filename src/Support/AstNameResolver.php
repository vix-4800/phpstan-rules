<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

final class AstNameResolver
{
    public static function matchesClassName(Name $name, string $fqcn): bool
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return mb_ltrim($resolvedName->toString(), '\\') === $fqcn;
        }

        return mb_ltrim($name->toString(), '\\') === $fqcn
            || mb_substr($fqcn, mb_strrpos($fqcn, '\\') + 1) === $name->toString();
    }

    public static function classConstFetchMatches(Expr $expr, string $fqcn): bool
    {
        return $expr instanceof ClassConstFetch
            && $expr->name instanceof Identifier
            && $expr->name->toString() === 'class'
            && $expr->class instanceof Name
            && self::matchesClassName($expr->class, $fqcn);
    }

    public static function resolveName(Name $name): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return mb_ltrim($resolvedName->toString(), '\\');
        }

        return mb_ltrim($name->toString(), '\\');
    }

    public static function qualifyName(string $name, string $namespaceName): string
    {
        if (str_contains($name, '\\') || $namespaceName === '') {
            return mb_ltrim($name, '\\');
        }

        return $namespaceName . '\\' . $name;
    }
}
