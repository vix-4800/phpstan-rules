<?php

declare(strict_types=1);

namespace yii\base {
    class Controller
    {
    }
}

namespace yii\web {
    class Controller extends \yii\base\Controller
    {
    }
}

namespace yii\filters {
    class VerbFilter
    {
    }
}

namespace Fixtures {
    final class Model
    {
        public function save(): bool
        {
            return true;
        }

        public function delete(): bool
        {
            return true;
        }
    }

    final class MutatingController extends \yii\web\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\VerbFilter::class,
                    'actions' => [
                        'create' => ['POST'],
                        'update' => ['GET', 'POST'],
                        'delete' => ['OPTIONS'],
                    ],
                ],
            ];
        }

        public function actionCreate(Model $model): void
        {
            $model->save();
        }

        public function actionUpdate(Model $model): void
        {
            $model->save();
        }

        public function actionDelete(Model $model): void
        {
            $model->delete();
        }

        public function actionIndex(): void
        {
        }
    }
}
