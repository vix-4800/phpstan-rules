<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

final readonly class YiiControllerBehavior
{
    public function __construct(
        private Array_ $node,
    ) {
        //
    }

    public function node(): Array_
    {
        return $this->node;
    }

    public function appliesToAction(YiiControllerAction $action): bool
    {
        $only = $this->stringListItem('only');

        if ($only !== null && !in_array($action->id(), $only, true)) {
            return false;
        }

        $except = $this->stringListItem('except');

        return $except === null || !in_array($action->id(), $except, true);
    }

    public function arrayItem(string $key): ?Expr
    {
        foreach ($this->node->items as $item) {
            if (!$item->key instanceof String_) {
                continue;
            }

            if ($item->key->value !== $key) {
                continue;
            }

            return $item->value;
        }

        return null;
    }

    /**
     * @param string $key
     *
     * @return list<string>|null
     */
    public function stringListItem(string $key): ?array
    {
        $value = $this->arrayItem($key);

        if (!$value instanceof Array_) {
            return null;
        }

        $strings = [];

        foreach ($value->items as $item) {
            if (!$item->value instanceof String_) {
                return null;
            }

            $strings[] = $item->value->value;
        }

        return $strings;
    }
}
