<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\Class_;

final readonly class YiiInitClass
{
    public function __construct(
        private Class_ $node,
        private ?YiiMethod $initMethod,
    ) {
        //
    }

    public function node(): Class_
    {
        return $this->node;
    }

    public function initMethod(): ?YiiMethod
    {
        return $this->initMethod;
    }

    public function hasInitMethod(): bool
    {
        return $this->initMethod !== null;
    }
}
