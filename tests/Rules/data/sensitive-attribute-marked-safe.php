<?php

declare(strict_types=1);

namespace yii\base {
    class Model
    {
    }
}

namespace Fixtures {
    final class Book extends \yii\base\Model
    {
        public const string SCENARIO_ADMIN = 'admin';

        public function rules(): array
        {
            return [
                [['title'], 'safe'],
                [['status'], 'in', 'range' => ['draft', 'published'], 'on' => self::SCENARIO_ADMIN],
                [['created_by'], 'integer', 'except' => 'create'],
                [['auth_key'], 'unsafe'],
            ];
        }
    }

    final class UnsafeBook extends \yii\base\Model
    {
        public function rules(): array
        {
            return [
                [['title', 'status', 'created_by'], 'safe'],
                [['user_id'], 'integer'],
                [['auth_key'], 'string', 'max' => 64],
            ];
        }
    }
}
