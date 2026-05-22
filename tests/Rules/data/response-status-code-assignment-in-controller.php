<?php

declare(strict_types=1);

namespace {
    final class Yii
    {
        public static \Fixtures\Application $app;
    }
}

namespace yii\base {
    class Controller
    {
    }
}

namespace yii\web {
    final class Response
    {
        public function setStatusCode(int $statusCode): self
        {
            return $this;
        }
    }

    class Controller extends \yii\base\Controller
    {
        public function asJson(mixed $data): Response
        {
            return new Response();
        }
    }
}

namespace Fixtures {
    use Yii;
    use yii\web\Response;

    final class Application
    {
        public Response $response;

        public function __construct()
        {
            $this->response = new Response();
        }
    }

    final class StatusCodeController extends \yii\web\Controller
    {
        public function actionCreate(): Response
        {
            return $this->asJson(['success' => true])->setStatusCode(201);
        }

        public function actionUpdate(): Response
        {
            Yii::$app->response->statusCode = 202;

            return $this->asJson(['updated' => true]);
        }

        public function actionDelete(): void
        {
            \Yii::$app->response->statusCode = 204;
        }
    }

    final class StatusCodeService
    {
        public function setCode(): void
        {
            Yii::$app->response->statusCode = 500;
        }
    }
}
