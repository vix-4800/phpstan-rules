<?php

declare(strict_types=1);

namespace yii\base {
    class Controller
    {
    }

    class Action
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

    class AjaxFilter
    {
    }

    class VerbFilter
    {
    }
}

namespace Fixtures {
    use yii\base\Action;

    final class UnknownBehaviorActionController extends \yii\web\Controller
    {
        public function actions(): array
        {
            return [
                'captcha' => ['class' => Action::class],
                'status' => Action::class,
            ];
        }

        public function behaviors(): array
        {
            return [
                'access' => [
                    'class' => \yii\filters\AccessControl::class,
                    'only' => ['index', 'captcha', 'missing-only'],
                    'except' => ['status', 'missing-except'],
                    'rules' => [
                        ['allow' => true, 'actions' => ['index', 'missing-rule']],
                    ],
                ],
                'ajax' => [
                    'class' => \yii\filters\AjaxFilter::class,
                    'only' => ['status', 'missing-ajax'],
                ],
                'verbs' => [
                    'class' => \yii\filters\VerbFilter::class,
                    'actions' => [
                        'index' => ['GET'],
                        'missing-verb' => ['POST'],
                    ],
                ],
            ];
        }

        public function actionIndex(): void
        {
        }
    }
}
