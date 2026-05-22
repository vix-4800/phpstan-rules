<?php

declare(strict_types=1);

namespace {
    final class Yii
    {
        public static object $app;
    }
}

namespace yii\base {
    class Controller
    {
        public function asJson(mixed $data): mixed
        {
            return $data;
        }

        public function asXml(mixed $data): mixed
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
        public const string FORMAT_HTML = 'html';
        public const string FORMAT_JSON = 'json';
        public const string FORMAT_XML = 'xml';

        public string $format = self::FORMAT_HTML;
    }
}

namespace Fixtures {
    use yii\web\Response;

    final class ApiController extends \yii\web\Controller
    {
        public function actionJson(): void
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
        }

        public function actionXml(): void
        {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_XML;
        }

        public function actionOk(): mixed
        {
            return $this->asJson(['ok' => true]);
        }

        public function actionHtml(): void
        {
            \Yii::$app->response->format = Response::FORMAT_HTML;
        }
    }

    final class ResponseService
    {
        public function setJson(): void
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
        }
    }
}

namespace {
    Yii::$app = (object) ['response' => new \yii\web\Response()];
}
