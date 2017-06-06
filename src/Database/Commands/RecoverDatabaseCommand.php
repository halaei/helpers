<?php

namespace Halaei\Helpers\Database\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RecoverDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:recover {--connection=mysql} {--drop=false} {file}';

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
            return $this->recoverMySql($config);
        }
        throw new \InvalidArgumentException('driver not supported');
    }

    private function recoverMySql(array $config)
    {
        $process = new Process([
            __DIR__.'/scripts/mysqlimport',
            '--default-character-set', $config['charset'],
            '--host', $config['host'],
            '--port', $config['port'],
            '--user', $config['username'],
            '--password='.$config['password'], // todo: escape especial characters
            '--database', $config['database'],
        ], null, null, $this->getBackupFile());

        $status = $process->run(function ($type, $line) {
            echo $type.': '.$line;
        });
        if ($status) {
            $this->error('mysql has terminated with status: '.$status);
        } else {
            $this->info('mysql has successfully ended');
        }
    }

    private function getBackupFile()
    {
        return fopen(storage_path('backup/'.$this->argument('file')), 'r');
    }
}
