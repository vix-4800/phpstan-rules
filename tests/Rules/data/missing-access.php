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
    class AccessControl
    {
    }
}

namespace Fixtures {
    final class NoAccessController extends \yii\rest\Controller
    {
        public function actionIndex(): void
        {
        }
    }

    final class PartialAccessController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                'access' => [
                    'class' => \yii\filters\AccessControl::class,
                    'rules' => [
                        ['actions' => ['update-telegram-channel-id'], 'allow' => true],
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

    final class BehaviorOnlyController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\AccessControl::class,
                    'only' => ['index'],
                    'rules' => [
                        ['allow' => true],
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

    final class FullAccessController extends \yii\rest\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\AccessControl::class,
                    'rules' => [
                        ['allow' => true],
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
