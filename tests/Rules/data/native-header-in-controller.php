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

namespace Fixtures {
    final class HeaderController extends \yii\web\Controller
    {
        public function actionJson(): void
        {
            header('Content-Type: application/json');
        }

        public function behaviors(): array
        {
            header('X-Controller-Behavior: value');

            return [];
        }

        private function sendHeader(): void
        {
            header('X-Private: value');
        }
    }

    final class HeaderService
    {
        public function send(): void
        {
            header('X-Test: value');
        }
    }

    final class RestHeaderController extends \yii\rest\Controller
    {
        public function actionJson(): void
        {
            header('X-Rest: value');
        }
    }
}
