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

namespace yii\rest {
    class Controller extends \yii\web\Controller
    {
    }
}

namespace Fixtures {
    final class ValidWebController extends \yii\web\Controller
    {
        public function behaviors(): array
        {
            return [];
        }

        public function beforeAction(\yii\base\Action $action): bool
        {
            return true;
        }

        public function actionIndex(): void
        {
        }

        public function helper(): void
        {
        }

        protected function loadModel(): void
        {
        }

        private function actionInternal(): void
        {
        }

        public function action(): void
        {
        }
    }

    final class RestController extends \yii\rest\Controller
    {
        protected function verbs(): array
        {
            return [];
        }
    }

    final class BaseController extends \yii\base\Controller
    {
        protected function helper(): void
        {
        }
    }
}
