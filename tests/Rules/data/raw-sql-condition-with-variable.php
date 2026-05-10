<?php

declare(strict_types=1);

namespace Fixtures {
    final class Query
    {
        public function where(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function andWhere(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function orWhere(array|string $condition, array $params = []): self
        {
            return $this;
        }
    }

    function conditions(Query $query, int $userId): void
    {
        $query->where(['id' => $userId]);
        $query->where('id = :id', [':id' => $userId]);
        $query->where('active = 1');
        $query->where("id = $userId");
        $query->andWhere('id = ' . $userId);
        $query->orWhere('status = ' . 'active');
    }
}
