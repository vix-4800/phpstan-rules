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
    class AccessControl
    {
    }
}

namespace Fixtures {
    final class PublicAllowController extends \yii\web\Controller
    {
        public function behaviors(): array
        {
            return [
                [
                    'class' => \yii\filters\AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['view'],
                        ],
                        [
                            'allow' => true,
                        ],
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
            ];
        }

        public function actionIndex(): void
        {
        }
    }
}
