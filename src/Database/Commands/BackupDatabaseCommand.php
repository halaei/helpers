<?php

namespace Halaei\Helpers\Database\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--connection=mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

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
            return $this->backupMySql($config);
        }
        throw new \InvalidArgumentException('driver not supported');
    }

    private function backupMySql(array $config)
    {
        $backupPath = $this->getBackupPath();
        $logPath = $this->errorLogPath();
        $process = new Process([
            __DIR__.'/scripts/mysqldump',
            '--default-character-set', $config['charset'],
            '--host', $config['host'],
            '--port', $config['port'],
            '--user', $config['username'],
            '--password='.$config['password'], // todo: escape especial characters
            '--result-file', $backupPath,
            '--log-error', $logPath,
            $config['database']
        ], null, null, null, null, null);

        $status = $process->run(function ($type, $line) {
            echo $type.': '.$line;
        });
        if ($status) {
            $this->error("mysqldump has terminated with status: $status.\nSee $logPath for more information.");
        } else {
            $this->info("mysqldump has successfully generated the backup file:\n$backupPath");
        }
    }

    private function getBackupPath()
    {
        return storage_path('backup/'.$this->option('connection').'-'.Carbon::now()->format('Y-m-d-His').'.sql');
    }

    private function errorLogPath()
    {
        return storage_path('backup/'.$this->option('connection').'-error-log.txt');
    }
}
