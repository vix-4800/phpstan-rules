<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
    }
}

namespace yii\validators {
    class ImageValidator
    {
    }
}

namespace Fixtures {
    use yii\validators\ImageValidator;

    final class UploadForm extends \yii\base\Model
    {
        public function rules(): array
        {
            return [
                [['image'], 'image'],
                [['photo'], ImageValidator::class],
                [['avatar'], 'validator' => 'image'],
                [['thumb'], 'image', 'extensions' => 'png'],
                [['cover'], 'image', 'mimeTypes' => ['image/png']],
                [['preview'], 'image', 'maxSize' => 1024],
                [['banner'], 'image', 'minWidth' => 200],
                [['hero'], 'image', 'maxWidth' => 400],
                [['document'], 'file'],
            ];
        }
    }

    final class PlainClass
    {
        public function rules(): array
        {
            return [
                [['image'], 'image'],
            ];
        }
    }
}
