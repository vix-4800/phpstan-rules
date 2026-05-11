<?php

declare(strict_types=1);

namespace yii\base {
    class BaseObject
    {
        public function init(): void
        {
        }
    }

    class Component extends BaseObject
    {
        public function init(): void
        {
            parent::init();
        }
    }

    class Widget extends Component
    {
    }

    class Behavior extends Component
    {
    }
}

namespace yii\web {
    class AssetBundle extends \yii\base\Component
    {
    }
}

namespace Fixtures {
    final class MissingComponentInit extends \yii\base\Component
    {
        public function init(): void
        {
        }
    }

    final class MissingWidgetInit extends \yii\base\Widget
    {
        public function init(): void
        {
        }
    }

    final class MissingBehaviorInit extends \yii\base\Behavior
    {
        public function init(): void
        {
        }
    }

    final class MissingAssetBundleInit extends \yii\web\AssetBundle
    {
        public function init(): void
        {
        }
    }

    final class SafeWidget extends \yii\base\Widget
    {
        public function init(): void
        {
            parent::init();
        }
    }

    final class NoInitOverride extends \yii\base\Component
    {
    }

    final class PlainObject
    {
        public function init(): void
        {
        }
    }
}
