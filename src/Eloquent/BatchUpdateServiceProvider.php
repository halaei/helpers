<?php

namespace Halaei\Helpers\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class BatchUpdateServiceProvider extends ServiceProvider
{
    public function register()
    {
        Collection::macro('update', function () {
            $dirties = [];

            foreach ($this->items as $model) {
                $dirty = $model->getDirty();
                if (count($dirty)) {
                    $dirties[$model->getKey()] = $dirty;
                }
            }

            if (! count($dirties)) {
                return;
            }

            return $model->newQuery()->getQuery()->batchUpdate($model->getKeyName(), $dirties);
        });

        Builder::macro('batchUpdate', function ($keyName, array $values) {
            $this->whereIn($keyName, array_keys($values));

            $columns = [];

            foreach ($values as $key => $row) {
                foreach ($row as $column => $value) {
                    if (! array_key_exists($column, $columns)) {
                        $columns[$column] = [];
                    }
                    $columns[$column][$key] = $value;
                }
            }

            $params = [];
            $cases = [];

            foreach ($columns as $column => $rows) {
                $case = "CASE";
                foreach ($rows as $key => $value) {
                    $case .= " WHEN `$keyName` = ? THEN ?";
                    $params[] = $key;
                    $params[] = $value;
                }
                $case .= " ELSE `$column` END";
                $cases[$column] = $this->raw($case);
            }

            $bindings = array_values(array_merge($params, $this->getBindings()));

            $sql = $this->grammar->compileUpdate($this, $cases);

            return $this->connection->update($sql, $this->cleanBindings(
                $this->grammar->prepareBindingsForUpdate($bindings, $cases)
            ));
        });
    }
}
