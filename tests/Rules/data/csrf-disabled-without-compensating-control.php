<?php

declare(strict_types=1);

namespace yii\base {
    class Action
    {
    }

    class Controller
    {
        public function beforeAction(Action $action): bool
        {
            return true;
        }
    }
}

namespace yii\web {
    class Controller extends \yii\base\Controller
    {
        public bool $enableCsrfValidation = true;
    }
}

namespace Fixtures {
    final class WebhookController extends \yii\web\Controller
    {
        public function actionWebhook(): void
        {
            $this->enableCsrfValidation = false;
        }

        public function beforeAction(\yii\base\Action $action): bool
        {
            $this->enableCsrfValidation = false;

            return parent::beforeAction($action);
        }

        public function actionIndex(): void
        {
            $this->enableCsrfValidation = true;
        }
    }

    final class PlainObject
    {
        public bool $enableCsrfValidation = true;

        public function actionWebhook(): void
        {
            $this->enableCsrfValidation = false;
        }
    }
}
