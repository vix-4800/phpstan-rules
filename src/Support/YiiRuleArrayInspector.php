<?php

declare(strict_types=1);

namespace Vix\PhpstanRules\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class YiiRuleArrayInspector
{
    public static function findRulesMethod(Class_ $class): ?ClassMethod
    {
        foreach ($class->getMethods() as $method) {
            if ($method->name->toString() === 'rules') {
                return $method;
            }
        }

        return null;
    }

    public static function validatorExpr(Array_ $rule): ?Expr
    {
        $position = 0;

        foreach ($rule->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === 'validator') {
                return $item->value;
            }

            if ($item->key !== null) {
                continue;
            }

            if ($position === 1) {
                return $item->value;
            }

            ++$position;
        }

        return null;
    }

    public static function attributeExpr(Array_ $rule): ?Expr
    {
        foreach ($rule->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === 'attributes') {
                return $item->value;
            }
        }

        return $rule->items[0]->value ?? null;
    }

    /**
     * @param list<string> $keys
     */
    public static function hasAnyKey(Array_ $rule, array $keys): bool
    {
        foreach ($rule->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if (in_array($item->key->value, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    public static function stringListFromArrayItem(Array_ $array, string $key): ?array
    {
        $value = YiiControllerBehavior::arrayItemFromArray($array, $key);

        if (!$value instanceof Array_) {
            return null;
        }

        return self::stringList($value);
    }

    /**
     * @return list<string>|null
     */
    public static function stringList(Array_ $array): ?array
    {
        $strings = [];

        foreach ($array->items as $item) {
            if (!$item->value instanceof String_) {
                return null;
            }

            $strings[] = $item->value->value;
        }

        return $strings;
    }
}
