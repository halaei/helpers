<?php

namespace Halaei\Helpers\Database\Commands;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            BackupDatabaseCommand::class,
            RestoreDatabaseCommand::class,
        ]);
    }
}
