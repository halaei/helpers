<?php

namespace Halaei\Helpers\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class EloquentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBatchUpdate();
        $this->registerInsertIgnore();
    }

    public function registerBatchUpdate()
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

    public function registerInsertIgnore()
    {
        Builder::macro('insertIgnore', function (array $values) {
            if (empty($values)) {
                return true;
            }

            // Since every insert gets treated like a batch insert, we will make sure the
            // bindings are structured in a way that is convenient for building these
            // inserts statements by verifying the elements are actually an array.
            if (! is_array(reset($values))) {
                $values = [$values];
            }

            // Since every insert gets treated like a batch insert, we will make sure the
            // bindings are structured in a way that is convenient for building these
            // inserts statements by verifying the elements are actually an array.
            else {
                foreach ($values as $key => $value) {
                    ksort($value);
                    $values[$key] = $value;
                }
            }

            // We'll treat every insert like a batch insert so we can easily insert each
            // of the records into the database consistently. This will make it much
            // easier on the grammars to just handle one type of record insertion.
            $bindings = [];

            foreach ($values as $record) {
                foreach ($record as $value) {
                    $bindings[] = $value;
                }
            }

            $sql = $this->grammar->compileInsert($this, $values);

            // Replace insert with insert ignore.
            $sql = preg_replace('/^insert/', 'insert ignore', $sql);

            // Once we have compiled the insert statement's SQL we can execute it on the
            // connection and return a result as a boolean success indicator as that
            // is the same type of result returned by the raw connection instance.
            $bindings = $this->cleanBindings($bindings);

            return $this->connection->insert($sql, $bindings);

        });
    }
}
