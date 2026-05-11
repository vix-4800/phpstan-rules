<?php

declare(strict_types=1);

namespace {
    final class Yii
    {
        public static \yii\Application $app;
    }
}

namespace yii {
    final class Application
    {
        public UserComponent $user;

        public function getUser(): UserComponent
        {
            return $this->user;
        }
    }

    final class UserComponent
    {
        public int $id = 1;
        public Identity $identity;

        public function getId(): int
        {
            return $this->id;
        }

        public function getIdentity(): Identity
        {
            return $this->identity;
        }
    }

    final class Identity
    {
        public int $id = 1;
    }
}

namespace yii\db {
    class Query
    {
        public function where(array $condition): self
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

        public function one(): ?array
        {
            return null;
        }

        public function count(): int
        {
            return 0;
        }

        public function exists(): bool
        {
            return true;
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

        public function andWhere(array $condition): self
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

        public function one(): ?User
        {
            return null;
        }

        public function count(): int
        {
            return 0;
        }

        public function exists(): bool
        {
            return true;
        }
    }

    final class User
    {
        public int $id = 1;

        public static function find(): ActiveQuery
        {
            return new ActiveQuery();
        }

        public static function findOne(mixed $condition): ?self
        {
            return null;
        }
    }

    final class Post
    {
        public static function find(): ActiveQuery
        {
            return new ActiveQuery();
        }

        public static function findOne(mixed $condition): ?self
        {
            return null;
        }
    }

    function countLoadedAll(): int
    {
        return count(User::find()->where(['active' => true])->all());
    }

    function sizeofLoadedColumn(): int
    {
        return sizeof((new \yii\db\Query())->where(['active' => true])->column());
    }

    function oneNotNull(): bool
    {
        return User::find()->where(['id' => 1])->one() !== null;
    }

    function oneNull(): bool
    {
        return User::find()->where(['id' => 1])->one() === null;
    }

    function nullNotEqualOne(): bool
    {
        return null != User::find()->where(['id' => 1])->one();
    }

    function nullEqualOne(): bool
    {
        return null == User::find()->where(['id' => 1])->one();
    }

    function countGreaterThanZero(): bool
    {
        return User::find()->where(['active' => true])->count() > 0;
    }

    function countGreaterOrEqualOne(): bool
    {
        return User::find()->where(['active' => true])->count() >= 1;
    }

    function countNotEqualZero(): bool
    {
        return User::find()->where(['active' => true])->count() != 0;
    }

    function countNotIdenticalZero(): bool
    {
        return User::find()->where(['active' => true])->count() !== 0;
    }

    function countEqualZero(): bool
    {
        return User::find()->where(['active' => true])->count() == 0;
    }

    function countIdenticalZero(): bool
    {
        return User::find()->where(['active' => true])->count() === 0;
    }

    function countSmallerThanOne(): bool
    {
        return User::find()->where(['active' => true])->count() < 1;
    }

    function countSmallerOrEqualZero(): bool
    {
        return User::find()->where(['active' => true])->count() <= 0;
    }

    function zeroSmallerThanCount(): bool
    {
        return 0 < User::find()->where(['active' => true])->count();
    }

    function oneSmallerOrEqualCount(): bool
    {
        return 1 <= User::find()->where(['active' => true])->count();
    }

    function zeroNotEqualCount(): bool
    {
        return 0 != User::find()->where(['active' => true])->count();
    }

    function zeroNotIdenticalCount(): bool
    {
        return 0 !== User::find()->where(['active' => true])->count();
    }

    function zeroEqualCount(): bool
    {
        return 0 == User::find()->where(['active' => true])->count();
    }

    function zeroIdenticalCount(): bool
    {
        return 0 === User::find()->where(['active' => true])->count();
    }

    function oneGreaterThanCount(): bool
    {
        return 1 > User::find()->where(['active' => true])->count();
    }

    function zeroGreaterOrEqualCount(): bool
    {
        return 0 >= User::find()->where(['active' => true])->count();
    }

    function countLoadedAllComparison(): bool
    {
        return count(User::find()->where(['active' => true])->all()) >= 1;
    }

    function countLoadedAllEmptyComparison(): bool
    {
        return count(User::find()->where(['active' => true])->all()) === 0;
    }

    function currentUserById(): ?User
    {
        return User::findOne(\Yii::$app->user->id);
    }

    function currentUserByGetId(): ?User
    {
        return User::findOne(\Yii::$app->user->getId());
    }

    function currentUserByIdentityId(): ?User
    {
        return User::findOne(\Yii::$app->user->identity->id);
    }

    function currentUserByGetIdentityId(): ?User
    {
        return User::findOne(\Yii::$app->getUser()->getIdentity()->id);
    }

    function currentUserByArrayCondition(): ?User
    {
        return User::findOne(['id' => \Yii::$app->user->id]);
    }

    function safeExists(): bool
    {
        return User::find()->where(['active' => true])->exists();
    }

    function safeCountForExactNumber(): bool
    {
        return User::find()->where(['active' => true])->count() > 10;
    }

    function safeFindOneDifferentUser(): ?Post
    {
        return Post::findOne(['id' => 123]);
    }

    function safeNativeCount(array $items): bool
    {
        return count($items) > 0;
    }
}
