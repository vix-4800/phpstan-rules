<?php

declare(strict_types=1);

namespace yii\base {
    class Controller
    {
        public function asJson(mixed $data): mixed
        {
            return $data;
        }

        public function render(string $view, array $params = []): string
        {
            return $view;
        }

        public function renderAjax(string $view, array $params = []): string
        {
            return $view;
        }
    }
}

namespace yii\web {
    class Controller extends \yii\base\Controller
    {
    }
}

namespace Fixtures {
    final class MixedResponseController extends \yii\web\Controller
    {
        public bool $isAjax = false;

        public function actionIndex(): mixed
        {
            if ($this->isAjax) {
                return $this->asJson(['ok' => true]);
            }

            return $this->render('index');
        }

        public function actionShow(): string
        {
            if ($this->isAjax) {
                return $this->renderAjax('show');
            }

            return $this->render('show');
        }

        public function actionStatus(): mixed
        {
            return $this->asJson(['ok' => true]);
        }

        public function actionPreview(): mixed
        {
            if ($this->isAjax) {
                return $this->renderAjax('preview');
            }

            return $this->asJson(['preview' => true]);
        }
    }
}
