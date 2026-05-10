<?php

declare(strict_types=1);

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

        public function page(int $page, int $pageSize): self
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

    function unbounded(): array
    {
        return Post::find()->where(['active' => true])->all();
    }

    function limited(): array
    {
        return Post::find()->where(['active' => true])->limit(50)->all();
    }

    function paginated(): array
    {
        return Post::find()->where(['active' => true])->page(1, 50)->all();
    }

    final class PostService
    {
        public function all(): array
        {
            return Post::find()->where(['active' => true])->all();
        }
    }
}
