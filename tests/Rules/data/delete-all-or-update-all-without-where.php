<?php

declare(strict_types=1);

namespace Fixtures {
    final class Record
    {
        public static function deleteAll(mixed $condition = '', array $params = []): int
        {
            return 0;
        }

        public static function updateAll(array $attributes, mixed $condition = '', array $params = []): int
        {
            return 0;
        }
    }

    function bulkWrites(string $status): void
    {
        Record::deleteAll(['status' => $status]);
        Record::deleteAll(condition: ['status' => $status]);
        Record::updateAll(['status' => $status], ['id' => 1]);
        Record::updateAll(['status' => $status], condition: ['id' => 1]);

        Record::deleteAll();
        Record::deleteAll('');
        Record::deleteAll([]);
        Record::deleteAll(null);

        Record::updateAll(['status' => $status], ['id' => 1]);
        Record::updateAll(attributes: ['status' => $status], condition: ['id' => 1]);

        Record::updateAll(['status' => $status]);
        Record::updateAll(['status' => $status], '');
        Record::updateAll(['status' => $status], []);
        Record::updateAll(['status' => $status], null);
        Record::updateAll(attributes: ['status' => $status]);
    }
}
