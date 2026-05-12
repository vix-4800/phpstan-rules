<?php

declare(strict_types=1);

namespace yii\db {
    class ActiveQuery
    {
        public function all(): array
        {
            return [];
        }

        public function with(string|array $with): self
        {
            return $this;
        }

        public function joinWith(string|array $with): self
        {
            return $this;
        }
    }
}

namespace Fixtures {
    final class Author
    {
        public string $name = '';
    }

    final class Book
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

    final class BookReport
    {
        public function withoutEagerLoading(): void
        {
            $books = Book::find()->all();

            foreach ($books as $book) {
                echo $book->author->name;
            }
        }

        public function withEagerLoading(): void
        {
            $books = Book::find()->with('author')->all();

            foreach ($books as $book) {
                echo $book->author->name;
            }
        }

        public function withJoinEagerLoading(): void
        {
            $books = Book::find()->joinWith(['author'])->all();

            foreach ($books as $book) {
                echo $book->author->name;
            }
        }

        public function relationOutsideLoop(): void
        {
            $books = Book::find()->all();
            $book = $books[0];

            echo $book->author->name;
        }

        public function nonRelationPropertyInLoop(): void
        {
            $books = Book::find()->all();

            foreach ($books as $book) {
                echo $book->title;
            }
        }
    }
}
