<?php

namespace Halaei\Helpers\Eloquent\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupTableToFileSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-table {database} {table} {disk} {dir} {--truncate} {--auto-increment=id} {--mysqldump=mysqldump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup a table into file system';

    /**
     * @var Carbon|null
     */
    private $date;

    public function handle()
    {
        event('db:backup-table:starting', [
            'arguments' => $this->arguments(),
            'options' => $this->options(),
        ]);
        $this->date = now();
        $path = $this->upload($this->compress($this->dump()));
        $this->truncate();
        event('db:backup-table:done', [
            'arguments' => $this->arguments(),
            'options' => $this->options(),
            'path' => $path,
        ]);
    }

    private function dump(): string
    {
        $dir = storage_path("app/backup/{$this->date->format('Y-m-d_H-i-s')}");
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $table = $this->getTable();
        $destination = "$dir/$table.sql";
        $process = new Process([
            $this->option('mysqldump'),
            // Connection Options
            '--compress', // Deprecated in MySql 8.0.18
            '--compression-algorithms=zlib',
            '--host='.$this->connection()->getConfig('host'),
            '--password='.$this->connection()->getConfig('password'),
            '--port='.$this->connection()->getConfig('port'),
            // '--protocol='.$this->protocol(),
            '--user='.$this->connection()->getConfig('username'),
            '--skip-opt', // Disable --opt group and re-enable specific options later in the command
            // DDL Options
            '--no-create-db',
            '--no-create-info',
            '--no-tablespaces',
            // Debug Options
            '--default-character-set='.$this->connection()->getConfig('charset'),
            // Format Options
            '--result-file='.$destination,
            '--complete-insert',
            // Performance Options
            '--extended-insert',
            '--insert-ignore',
            '--quick', // ?

            $this->database(),
            $table,
        ], null, null, null, null);
        $this->warn("mysqldump $table > $destination");
        $process->mustRun();
        $this->info("mysqldump done");
        return $destination;
    }

    private function compress(string $src): string
    {
        $destination = "$src.tar.gz";
        $process = new Process([
            'tar', '-czf', $destination, basename($src),
        ], dirname($src), null, null, null);
        $this->warn("Compressing $src");
        $process->mustRun();
        unlink($src);
        $this->info("Compressed into $destination");
        return $destination;
    }

    private function upload(string $dump): string
    {
        $path = $this->argument('dir')."/{$this->date->format('Y-m-d_H-i-s')}/{$this->getTable()}.sql.tar.gz";
        $this->warn("Uploading $dump to storage $path");
        if (Storage::disk($this->argument('disk'))->put($path, fopen($dump, 'rb'), ['visibility' => 'private'])) {
            unlink($dump);
            $this->log("Upload was successful. To restore the archive run the following command:");
            $this->log("php artisan db:restore-dump {$this->argument('database')} {$this->argument('disk')} $path");
        } else {
            unlink($dump);
            throw new \Exception("Upload failed");
        }
        return $path;
    }

    private function truncate()
    {
        if ($this->option('truncate')) {
            $autoIncrement = $this->getAutoIncrementValue();
            $this->warn("AUTO_INCREMENT = $autoIncrement");
            $this->warn("Truncating table");
            $this->connection()->statement("truncate table {$this->getTable()}");
            $this->info("Table was truncated");
            $this->setAutoIncrementValue($autoIncrement);
            $this->info("AUTO_INCREMENT was recovered");
        }
    }

    private function getAutoIncrementValue()
    {
        $column = $this->option('auto-increment');
        if (! $column) {
            return null;
        }
        return $this->connection()->select("SELECT MAX(`$column`) AS id FROM {$this->getTable()}")[0]->id ?? null;
    }

    private function setAutoIncrementValue($value)
    {
        try {
            if ($value) {
                $inc = (int) $value + 1000;
                $this->connection()->statement("ALTER TABLE `{$this->getTable()}` AUTO_INCREMENT = $inc");
            }
        } catch (\Exception $e) {
            $this->error('Cannot set auto increment: '.$e->getMessage());
            report($e);
        }
    }

    private function getTable(): string
    {
        return $this->connection()->getTablePrefix().$this->argument('table');
    }

    private function database(): string
    {
        return $this->connection()->getDatabaseName();
    }

    private function connection(): Connection
    {
        return DB::connection($this->argument('database'));
    }

    private function log($message)
    {
        $this->info($message);
        Log::info($message);
    }
}
