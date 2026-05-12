<?php

declare(strict_types=1);

namespace Fixtures {
    class Base
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            return true;
        }
    }

    final class SafeController extends \yii\web\Controller
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            if (!parent::beforeAction($action)) {
                return false;
            }

            return true;
        }
    }

    final class ReturnParentController extends \yii\web\Controller
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            return parent::beforeAction($action);
        }
    }

    final class IgnoredParentController extends \yii\web\Controller
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            parent::beforeAction($action);

            return true;
        }
    }

    final class PlainClass extends Base
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            parent::beforeAction($action);

            return true;
        }
    }

    final class IgnoredParentAction extends \yii\base\Action
    {
        public function beforeAction(\yii\base\Action $action): bool
        {
            parent::beforeAction($action);

            return true;
        }
    }
}
