<?php

declare(strict_types=1);

namespace yii\db {
    class Query
    {
        public function where(array $condition): self
        {
            return $this;
        }

        public function limit(int $limit): self
        {
            return $this;
        }

        public function one(): array
        {
            return [];
        }
    }
}

namespace Fixtures {
    final class ActiveQuery
    {
        public function where(array $condition): self
        {
            return $this;
        }

        public function limit(int $limit): self
        {
            return $this;
        }

        public function one(): array
        {
            return [];
        }
    }

    final class Post
    {
        public static function find(): ActiveQuery
        {
            return new ActiveQuery();
        }
    }

    function activeQueryOne(): array
    {
        return Post::find()->where(['id' => 1])->one();
    }

    function queryOne(): array
    {
        return (new \yii\db\Query())->where(['id' => 1])->one();
    }

    function limitedOne(): array
    {
        return Post::find()->where(['id' => 1])->limit(1)->one();
    }
}
