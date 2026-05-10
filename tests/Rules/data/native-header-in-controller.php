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

namespace Fixtures {
    final class HeaderController extends \yii\web\Controller
    {
        public function actionJson(): void
        {
            header('Content-Type: application/json');
        }
    }

    final class HeaderService
    {
        public function send(): void
        {
            header('X-Test: value');
        }
    }
}
