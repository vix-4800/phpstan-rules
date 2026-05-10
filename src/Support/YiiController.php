<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\Class_;

final readonly class YiiController
{
    /**
     * @param list<YiiControllerAction>   $actions
     * @param list<YiiControllerBehavior> $behaviors
     */
    public function __construct(
        private Class_ $node,
        private array $actions,
        private array $behaviors,
        private YiiControllerFactory $factory,
    ) {
        //
    }

    public function node(): Class_
    {
        return $this->node;
    }

    /**
     * @return list<YiiControllerAction>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * @return list<YiiControllerBehavior>
     */
    public function behaviorsByClass(string $behaviorClassName): array
    {
        return array_values(array_filter(
            $this->behaviors,
            fn(YiiControllerBehavior $behavior): bool => $this->factory->behaviorIsClass($behavior, $behaviorClassName),
        ));
    }
}
