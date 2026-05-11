<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
        public function beforeValidate(): bool
        {
            return true;
        }

        public function afterValidate(): void
        {
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

        public function save(bool $runValidation = true): bool
        {
            return $runValidation;
        }

        public function update(bool $runValidation = true): bool
        {
            return $runValidation;
        }

        public function delete(): bool
        {
            return true;
        }

        public function beforeValidate(): bool
        {
            return parent::beforeValidate();
        }

        public function afterValidate(): void
        {
            parent::afterValidate();
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
    final class RecursiveAfterFindModel extends \yii\db\ActiveRecord
    {
        public function afterFind(): void
        {
            $this->save(false);
        }
    }

    final class RecursiveBeforeSaveModel extends \yii\db\ActiveRecord
    {
        public function beforeSave(bool $insert): bool
        {
            $this->update(false);

            return parent::beforeSave($insert);
        }
    }

    final class RecursiveAfterDeleteModel extends \yii\db\ActiveRecord
    {
        public function afterDelete(): void
        {
            $this->delete();
            parent::afterDelete();
        }
    }

    final class RecursiveAfterValidateModel extends \yii\db\ActiveRecord
    {
        public function afterValidate(): void
        {
            $this->save(false);
        }
    }

    final class HelperModel
    {
        public function save(bool $runValidation = true): bool
        {
            return $runValidation;
        }
    }

    final class SafeLifecycleModel extends \yii\db\ActiveRecord
    {
        public function afterFind(): void
        {
            $helper = new HelperModel();
            $helper->save(false);
            parent::afterFind();
        }

        public function beforeValidate(): bool
        {
            return parent::beforeValidate();
        }
    }
}
