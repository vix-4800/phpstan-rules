<?php

declare(strict_types=1);

namespace Vix\PhpstanYiiPolicyRules\Support;

use PhpParser\Node\Stmt\Class_;

final readonly class YiiController
{
    /**
     * @param Class_                      $node
     * @param list<YiiControllerAction>   $actions
     * @param list<string>                $externalActionIds
     * @param list<YiiControllerBehavior> $behaviors
     * @param YiiControllerFactory        $factory
     */
    public function __construct(
        private Class_ $node,
        private array $actions,
        private array $externalActionIds,
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
     * @return list<string>
     */
    public function actionIds(): array
    {
        return array_values(array_unique([
            ...array_map(
                static fn(YiiControllerAction $action): string => $action->id(),
                $this->actions,
            ),
            ...$this->externalActionIds,
        ]));
    }

    public function hasActionId(string $actionId): bool
    {
        return in_array($actionId, $this->actionIds(), true);
    }

    /**
     * @return list<YiiControllerBehavior>
     */
    public function behaviors(): array
    {
        return $this->behaviors;
    }

    /**
     * @param string $behaviorClassName
     *
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
