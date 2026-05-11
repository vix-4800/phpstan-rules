<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\ClassMethod;

final readonly class YiiControllerAction
{
    public function __construct(
        private ClassMethod $method,
        private string $id,
    ) {
        //
    }

    public function method(): ClassMethod
    {
        return $this->method;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function methodName(): string
    {
        return $this->method->name->toString();
    }

    public function line(): int
    {
        return $this->method->getStartLine();
    }

    public function actionName(): string
    {
        $name = lcfirst(mb_substr($this->methodName(), start: 6));

        return mb_strtolower(preg_replace('/([a-z])([A-Z])/', replacement: '$1-$2', subject: $name) ?? '');
    }
}
