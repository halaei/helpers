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
     * The timestamp of executing the command.
     *
     * @var Carbon
     */
    protected $timestamp;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->timestamp = Carbon::now();

        $config = config('database.connections.'.$this->option('connection'));
        if (! is_array($config)) {
            throw new \InvalidArgumentException('connection not found');
        }
        if ($config['driver'] == 'mysql') {
            return $this->runBackup($this->getMySqlBackup($config), 'sql');
        } elseif ($config['driver' == 'mongodb']) {
            return $this->runBackup($this->getMongoBackup($config), 'bson');
        }
        throw new \InvalidArgumentException('driver not supported');
    }

    protected function runBackup(Process $process, $extension)
    {
        $backupPath = $this->getBackupPath($extension);
        $logPath = $this->errorLogPath();

        $status = $process->run(function ($type, $line) {
            echo $type.': '.$line;
        });
        if ($status) {
            $this->error("Backup script has terminated with status: $status.\nSee $logPath for more information.");
        } else {
            $this->info("Backup script has successfully generated the backup file:\n$backupPath");
        }
    }

    protected function getMongoBackup(array $config)
    {
        return new Process([
            __DIR__.'/scripts/mongodump',
        ]);
    }

    protected function getMySqlBackup(array $config)
    {
        $backupPath = $this->getBackupPath('sql');
        $logPath = $this->errorLogPath();
        return new Process([
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
    }

    protected function getBackupPath($extension)
    {
        return storage_path(
            'backup/'.$this->option('connection').'-'.$this->timestamp->format('Y-m-d-His').'.'.$extension
        );
    }

    protected function errorLogPath()
    {
        return storage_path('backup/'.$this->option('connection').'-error-log.txt');
    }
}
