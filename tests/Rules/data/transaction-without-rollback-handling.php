<?php

declare(strict_types=1);

namespace yii\db {
    class Transaction
    {
        public function commit(): void
        {
        }

        public function rollBack(): void
        {
        }
    }

    class Connection
    {
        public function beginTransaction(): Transaction
        {
            return new Transaction();
        }
    }
}

namespace Fixtures {
    final class TransactionService
    {
        public function missingCatch(\yii\db\Connection $db): void
        {
            $transaction = $db->beginTransaction();
            $transaction->commit();
        }

        public function missingRollback(\yii\db\Connection $db): void
        {
            $transaction = $db->beginTransaction();

            try {
                $transaction->commit();
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        public function hasRollback(\yii\db\Connection $db): void
        {
            $transaction = $db->beginTransaction();

            try {
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        public function hasLowercaseRollback(\yii\db\Connection $db): void
        {
            $transaction = $db->beginTransaction();

            try {
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollback();
            }
        }
    }
}
