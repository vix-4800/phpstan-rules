<?php

declare(strict_types=1);

namespace yii\base {
    class Controller
    {
        public function asJson(mixed $data): mixed
        {
            return $data;
        }
    }
}

namespace yii\web {
    class Controller extends \yii\base\Controller
    {
    }

    class Response
    {
        public const string FORMAT_JSON = 'json';

        public string $format = '';
    }
}

namespace yii\filters {
    class AjaxFilter
    {
    }
}

namespace Fixtures {
    final class MissingAjaxFilterController extends \yii\web\Controller
    {
        public function actionSearch(): mixed
        {
            return $this->asJson(['ok' => true]);
        }

        public function actionIndex(): void
        {
        }
    }

    final class PartialAjaxFilterController extends \yii\web\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\AjaxFilter::class,
                    'only' => ['search'],
                ],
            ];
        }

        public function actionStatus(): void
        {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        }

        public function actionSearch(): mixed
        {
            return $this->asJson(['ok' => true]);
        }
    }

    final class FullAjaxFilterController extends \yii\web\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\AjaxFilter::class,
                ],
            ];
        }

        public function actionSearch(): mixed
        {
            return $this->asJson(['ok' => true]);
        }
    }
}
