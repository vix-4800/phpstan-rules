<?php

declare(strict_types=1);

namespace App {
    final class Model
    {
        public function save(bool $runValidation = true): bool
        {
            return true;
        }
    }

    function usesSave(Model $model): void
    {
        $model->save(false);
        $model->save(true);
        $model->save();
    }
}

namespace console\migrations {
    function migrationSave(\App\Model $model): void
    {
        $model->save(false);
    }
}
