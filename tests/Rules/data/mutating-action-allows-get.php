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

        public function updateAttributes(array $attributes): bool
        {
            return true;
        }

        public function updateCounters(array $counters): bool
        {
            return true;
        }

        public static function updateAllCounters(array $counters, array $condition): int
        {
            return 1;
        }

        public function insert(bool $runValidation = true): bool
        {
            return true;
        }

        public function saveAs(string $path): bool
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
                        'update-attributes' => ['GET'],
                        'update-counters' => ['GET'],
                        'update-all-counters' => ['GET'],
                        'insert' => ['HEAD'],
                        'rename-file' => ['GET'],
                        'save-as' => ['POST'],
                        'filesystem' => ['POST'],
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

        public function actionUpdateAttributes(Model $model): void
        {
            $model->updateAttributes(['status' => 'active']);
        }

        public function actionUpdateCounters(Model $model): void
        {
            $model->updateCounters(['visits' => 1]);
        }

        public function actionUpdateAllCounters(): void
        {
            Model::updateAllCounters(['visits' => 1], ['id' => 5]);
        }

        public function actionInsert(Model $model): void
        {
            $model->insert(false);
        }

        public function actionRenameFile(): void
        {
            rename('/tmp/source.txt', '/tmp/target.txt');
        }

        public function actionSaveAs(Model $model): void
        {
            $model->saveAs('/tmp/export.csv');
        }

        public function actionFilesystem(): void
        {
            unlink('/tmp/export.csv');
            mkdir('/tmp/archive');
            rmdir('/tmp/archive');
        }

        public function actionIndex(): void
        {
        }
    }
}
