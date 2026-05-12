<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
    }
}

namespace Fixtures {
    final class Book extends \yii\base\Model
    {
        public const string SCENARIO_UPDATE = 'update';

        public string $scenario = 'default';

        public array $attributes = [];

        public function load(array $data): bool
        {
            return true;
        }

        public function setAttributes(array $values): void
        {
        }

        public function setScenario(string $scenario): void
        {
        }
    }

    final class BookService
    {
        public function scenarioBeforeLoad(array $data): void
        {
            $model = new Book();
            $model->scenario = Book::SCENARIO_UPDATE;
            $model->load($data);
        }

        public function scenarioAfterLoad(array $data): void
        {
            $model = new Book();
            $model->load($data);
            $model->scenario = Book::SCENARIO_UPDATE;
        }

        public function scenarioAfterSetAttributes(array $data): void
        {
            $model = new Book();
            $model->setAttributes($data);
            $model->scenario = Book::SCENARIO_UPDATE;
        }

        public function scenarioAfterAttributesAssignment(array $data): void
        {
            $model = new Book();
            $model->attributes = $data;
            $model->scenario = Book::SCENARIO_UPDATE;
        }

        public function setScenarioAfterLoad(array $data): void
        {
            $model = new Book();
            $model->load($data);
            $model->setScenario(Book::SCENARIO_UPDATE);
        }

        public function reassignedModelAfterLoad(array $data): void
        {
            $model = new Book();
            $model->load($data);
            $model = new Book();
            $model->scenario = Book::SCENARIO_UPDATE;
        }

        public function scenarioAfterLoadInsideBranch(array $data): void
        {
            $model = new Book();

            if ($model->load($data)) {
                $model->scenario = Book::SCENARIO_UPDATE;
            }
        }
    }

    final class PlainLoader
    {
        public string $scenario = 'default';

        public function load(array $data): bool
        {
            return true;
        }
    }

    final class PlainLoaderService
    {
        public function falsePositiveGuard(array $data): void
        {
            $loader = new PlainLoader();
            $loader->load($data);
            $loader->scenario = 'updated';
        }
    }

    final class TypedModelService
    {
        public function typedModelParam(Book $model, array $data): void
        {
            $model->load($data);
            $model->scenario = Book::SCENARIO_UPDATE;
        }
    }

    final class TypedPlainLoaderService
    {
        public function typedPlainParam(PlainLoader $loader, array $data): void
        {
            $loader->load($data);
            $loader->scenario = 'updated';
        }
    }
}
