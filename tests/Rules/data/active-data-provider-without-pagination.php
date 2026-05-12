<?php

declare(strict_types=1);

namespace Fixtures {
    use yii\data\ActiveDataProvider;
    use yii\data\SqlDataProvider;

    final class Book
    {
        public static function find(): object
        {
            return new \stdClass();
        }
    }

    final class PlainService
    {
        public function getDataProvider(): ActiveDataProvider
        {
            return new ActiveDataProvider([
                'query' => Book::find(),
                'pagination' => false,
            ]);
        }
    }

    final class BookController extends \yii\web\Controller
    {
        public function actionSafe(): ActiveDataProvider
        {
            return new ActiveDataProvider([
                'query' => Book::find(),
                'pagination' => [
                    'pageSize' => 50,
                ],
            ]);
        }

        public function actionWithoutPagination(): ActiveDataProvider
        {
            return new ActiveDataProvider([
                'query' => Book::find(),
                'pagination' => false,
            ]);
        }

        public function actionSqlWithoutPagination(): SqlDataProvider
        {
            return new SqlDataProvider([
                'sql' => 'select * from book',
                'pagination' => false,
            ]);
        }
    }
}
