<?php

namespace Halaei\Helpers\Database\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RestoreDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {--connection=mysql} {--drop=false} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = config('database.connections.'.$this->option('connection'));
        if (! is_array($config)) {
            throw new \InvalidArgumentException('connection not found');
        }
        if ($config['driver'] == 'mysql') {
            return $this->runRestore($this->getMySqlRestore($config));
        } elseif ($config['driver' == 'mongodb']) {
            return $this->runRestore($this->getMongoRestore($config));
        }
        throw new \InvalidArgumentException('driver not supported');
    }

    protected function runRestore(Process $process)
    {
        $status = $process->run(function ($type, $line) {
            echo $type.': '.$line;
        });
        if ($status) {
            $this->error("Restore script has terminated with status: $status.");
        } else {
            $this->info("Restore script has successfully restored the database.");
        }
    }

    protected function getMySqlRestore(array $config)
    {
        return new Process([
            __DIR__.'/scripts/mysqlimport',
            '--default-character-set', $config['charset'],
            '--host', $config['host'],
            '--port', $config['port'],
            '--user', $config['username'],
            '--password='.$config['password'], // todo: escape especial characters
            '--database', $config['database'],
        ], null, null, $this->getBackupFile());
    }

    protected function getMongoRestore(array $config)
    {
        return new Process([
            __DIR__.'/scripts/mongolimport',
        ], null, null, $this->getBackupFile());
    }

    protected function getBackupFile()
    {
        return fopen(storage_path('backup/'.$this->argument('file')), 'r');
    }
}
