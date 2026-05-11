<?php

declare(strict_types=1);

namespace Fixtures {
    final class Command
    {
    }

    final class DbConnection
    {
        public function createCommand(string $sql = '', array $params = []): Command
        {
            return new Command();
        }
    }

    final class Application
    {
        public DbConnection $db;

        public function __construct()
        {
            $this->db = new DbConnection();
        }
    }

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

        public function filterWhere(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function andFilterWhere(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function having(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function join(string $type, array|string $table, array|string $on = [], array $params = []): self
        {
            return $this;
        }

        public function leftJoin(array|string $table, array|string $on = [], array $params = []): self
        {
            return $this;
        }

        public function innerJoin(array|string $table, array|string $on = [], array $params = []): self
        {
            return $this;
        }

        public function on(array|string $condition, array $params = []): self
        {
            return $this;
        }

        public function from(array|string $tables): self
        {
            return $this;
        }

        public function orderBy(array|string $columns): self
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
        $query->filterWhere("status = $userId");
        $query->leftJoin("orders_$userId o", 'o.user_id = user.id');
        $query->innerJoin('orders o', 'o.user_id = ' . $userId);
        $query->on('user_id = ' . $userId);
        $query->from('user_' . $userId);
        $query->orderBy("FIELD(status, $userId)");
        $query->join('LEFT JOIN', 'orders o', 'o.user_id = :id', [':id' => $userId]);
        $query->andFilterWhere(['status' => $userId]);
        $query->having('active = 1');
        $query->orWhere('status = ' . 'active');
    }

    function commands(int $userId, string $role): void
    {
        \Yii::$app->db->createCommand('DELETE FROM user WHERE id = :id', [':id' => $userId]);
        \Yii::$app->db->createCommand("UPDATE user SET role = '$role'");
        \Yii::$app->db->createCommand('DELETE FROM user WHERE id = ' . $userId);
    }
}

namespace {
    final class Yii
    {
        public static \Fixtures\Application $app;
    }

    Yii::$app = new \Fixtures\Application();
}
