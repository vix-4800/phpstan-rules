<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
    }
}

namespace yii\validators {
    class FileValidator
    {
    }
}

namespace Fixtures {
    use yii\validators\FileValidator;

    final class UploadForm extends \yii\base\Model
    {
        /**
         * @return list<array<mixed>>
         */
        public function rules(): array
        {
            return [
                [['file'], 'file'],
                [['image'], FileValidator::class],
                [['archive'], 'file', 'maxSize' => 1024],
                [['document'], 'file', 'extensions' => ['pdf']],
                [['photo'], 'file', 'mimeTypes' => ['image/jpeg']],
            ];
        }
    }

    final class PlainClassWithRules
    {
        /**
         * @return list<array<mixed>>
         */
        public function rules(): array
        {
            return [
                [['file'], 'file'],
            ];
        }
    }
}
