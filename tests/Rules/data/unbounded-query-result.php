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

        public function all(): array
        {
            return [];
        }

        public function column(): array
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

        public function asArray(): self
        {
            return $this;
        }

        public function indexBy(string $column): self
        {
            return $this;
        }

        public function all(): array
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

    function activeQueryAll(): array
    {
        return Post::find()->where(['active' => true])->all();
    }

    function queryAll(): array
    {
        return (new \yii\db\Query())->where(['active' => true])->all();
    }

    function queryColumn(): array
    {
        return (new \yii\db\Query())->where(['active' => true])->column();
    }

    function activeQueryLimited(): array
    {
        return Post::find()->where(['active' => true])->limit(10)->all();
    }

    function chainedButLimited(): array
    {
        return Post::find()->where(['active' => true])->asArray()->indexBy('id')->limit(10)->all();
    }
}
