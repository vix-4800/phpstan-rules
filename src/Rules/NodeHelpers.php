<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;

final class NodeHelpers
{
    public static function nameEquals(Node|string|null $name, string $expected): bool
    {
        if ($name instanceof Identifier || $name instanceof Name) {
            return mb_strtolower($name->toString()) === mb_strtolower($expected);
        }

        return is_string($name) && mb_strtolower($name) === mb_strtolower($expected);
    }

    public static function classNameEndsWith(?Name $name, string $expected): bool
    {
        if ($name === null) {
            return false;
        }

        $parts = $name->getParts();

        return mb_strtolower(end($parts)) === mb_strtolower($expected);
    }

    public static function optionArrayHasKey(?Arg $arg, string $key): bool
    {
        return $arg !== null && self::arrayHasKey($arg->value, $key);
    }

    /**
     * @param array<int|string, Arg|VariadicPlaceholder> $args
     * @param int                                        $index
     */
    public static function argAt(array $args, int $index): ?Arg
    {
        $arg = $args[$index] ?? null;

        return $arg instanceof Arg ? $arg : null;
    }

    public static function arrayHasKey(Expr $expr, string $key): bool
    {
        if (!$expr instanceof Array_) {
            return false;
        }

        foreach ($expr->items as $item) {
            if ($item->key === null) {
                continue;
            }

            if ($item->key instanceof String_ && mb_strtolower($item->key->value) === mb_strtolower($key)) {
                return true;
            }
        }

        return false;
    }

    public static function isFalseLike(Expr $expr): bool
    {
        if ($expr instanceof ConstFetch) {
            return self::nameEquals($expr->name, 'false');
        }

        return false;
    }

    public static function isZeroLike(Expr $expr): bool
    {
        if ($expr instanceof Int_) {
            return $expr->value === 0;
        }

        return self::isFalseLike($expr);
    }

    public static function isRemoteString(Expr $expr): bool
    {
        if ($expr instanceof String_) {
            return preg_match('#^https?://#i', $expr->value) === 1;
        }

        if ($expr instanceof Concat) {
            if (self::isRemoteString($expr->left)) {
                return true;
            }

            return self::isRemoteString($expr->right);
        }

        return false;
    }

    public static function isFunctionCall(Expr $expr, string $functionName): bool
    {
        return $expr instanceof FuncCall
            && $expr->name instanceof Name
            && self::nameEquals($expr->name, $functionName);
    }

    public static function isRequestCall(Expr $expr): bool
    {
        if ($expr instanceof MethodCall || $expr instanceof StaticCall) {
            return self::nameEquals($expr->name, 'request');
        }

        return false;
    }
}
