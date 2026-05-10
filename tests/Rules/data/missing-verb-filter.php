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

namespace yii\rest {
    class Controller extends \yii\web\Controller
    {
    }
}

namespace yii\filters {
    class VerbFilter
    {
    }
}

namespace Fixtures {
    final class NoVerbFilterController extends \yii\rest\Controller
    {
        public function actionIndex(): void
        {
        }
    }

    final class PartialVerbFilterController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                'verbs' => [
                    'class' => \yii\filters\VerbFilter::class,
                    'actions' => [
                        'update-telegram-channel-id' => ['POST'],
                    ],
                ],
            ];
        }

        public function actionUpdateTelegramChannelId(): void
        {
        }

        public function actionCreateUsersBot(): void
        {
        }
    }

    final class BehaviorOnlyVerbFilterController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\VerbFilter::class,
                    'only' => ['index'],
                    'actions' => [
                        'index' => ['GET'],
                        'create' => ['POST'],
                    ],
                ],
            ];
        }

        public function actionIndex(): void
        {
        }

        public function actionCreate(): void
        {
        }
    }

    final class FullVerbFilterController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\VerbFilter::class,
                    'actions' => [
                        'index' => ['GET', 'HEAD'],
                        'create' => ['POST'],
                    ],
                ],
            ];
        }

        public function actionIndex(): void
        {
        }

        public function actionCreate(): void
        {
        }
    }
}
