<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
        public function beforeValidate(): bool
        {
            return true;
        }

        public function afterFind(): void
        {
        }

        public function afterDelete(): void
        {
        }
    }
}

namespace yii\db {
    class BaseActiveRecord extends \yii\base\Model
    {
        public function beforeSave(bool $insert): bool
        {
            return true;
        }

        public function afterSave(bool $insert, array $changedAttributes): void
        {
        }

        public function beforeDelete(): bool
        {
            return true;
        }

        public function beforeValidate(): bool
        {
            return parent::beforeValidate();
        }

        public function afterFind(): void
        {
            parent::afterFind();
        }

        public function afterDelete(): void
        {
            parent::afterDelete();
        }
    }

    class ActiveRecord extends BaseActiveRecord
    {
    }
}

namespace Fixtures {
    final class MissingBeforeValidateModel extends \yii\db\ActiveRecord
    {
        public function beforeValidate(): bool
        {
            return true;
        }
    }

    final class MissingBeforeSaveModel extends \yii\db\ActiveRecord
    {
        public function beforeSave(bool $insert): bool
        {
            return $insert;
        }
    }

    final class MissingAfterSaveModel extends \yii\db\ActiveRecord
    {
        public function afterSave(bool $insert, array $changedAttributes): void
        {
            $changedAttributes = [];
        }
    }

    final class MissingAfterFindModel extends \yii\db\ActiveRecord
    {
        public function afterFind(): void
        {
        }
    }

    final class MissingAfterDeleteModel extends \yii\db\ActiveRecord
    {
        public function afterDelete(): void
        {
        }
    }

    final class MissingBeforeDeleteModel extends \yii\db\ActiveRecord
    {
        public function beforeDelete(): bool
        {
            return true;
        }
    }

    final class IgnoredBeforeValidateParentResultModel extends \yii\db\ActiveRecord
    {
        public function beforeValidate(): bool
        {
            parent::beforeValidate();

            return true;
        }
    }

    final class IgnoredBeforeSaveParentResultModel extends \yii\db\ActiveRecord
    {
        public function beforeSave(bool $insert): bool
        {
            parent::beforeSave($insert);

            return true;
        }
    }

    final class IgnoredBeforeDeleteParentResultModel extends \yii\db\ActiveRecord
    {
        public function beforeDelete(): bool
        {
            parent::beforeDelete();

            return true;
        }
    }

    final class SafeLifecycleModel extends \yii\db\ActiveRecord
    {
        public function beforeValidate(): bool
        {
            return parent::beforeValidate();
        }

        public function beforeSave(bool $insert): bool
        {
            if (!parent::beforeSave($insert)) {
                return false;
            }

            return true;
        }

        public function afterSave(bool $insert, array $changedAttributes): void
        {
            parent::afterSave($insert, $changedAttributes);
        }

        public function afterFind(): void
        {
            parent::afterFind();
        }

        public function afterDelete(): void
        {
            parent::afterDelete();
        }

        public function beforeDelete(): bool
        {
            return parent::beforeDelete();
        }
    }
}
