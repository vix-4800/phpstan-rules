<?php

declare(strict_types=1);

namespace Fixtures\Models {
    final class Author
    {
        public string $name = '';
    }

    final class ExternalBook
    {
        public Author $author;

        public static function find(): \yii\db\ActiveQuery
        {
            return new \yii\db\ActiveQuery();
        }

        public function getAuthor(): \yii\db\ActiveQuery
        {
            return new \yii\db\ActiveQuery();
        }
    }
}
