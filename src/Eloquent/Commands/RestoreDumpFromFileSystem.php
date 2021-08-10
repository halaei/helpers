<?php

namespace Halaei\Helpers\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\MountManager;
use Symfony\Component\Process\Process;

class RestoreDumpFromFileSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore-dump {database} {disk} {path} {--mysqlcli=mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore dump from filesystem';

    public function handle()
    {
        $this->restore($this->uncompress($this->mount()));
    }

    private function mount(): string
    {
        $mountManager = new MountManager([
            'remote' => Storage::disk($this->argument('disk'))->getDriver(),
            'local' => Storage::disk('local')->getDriver(),
        ]);
        $path = $this->argument('path');
        Storage::disk('local')->delete($path);
        $mountManager->copy('remote://'. $path, 'local://'. $path);
        return Storage::disk('local')->path($path);
    }

    private function uncompress(string $src)
    {
        $dst = Str::beforeLast($src, '.tar.gz');
        $process = new Process([
            'tar',
            '-xzf', $src,
        ], dirname($src), null, null, null);
        $process->mustRun();
        unlink($src);
        return $dst;
    }

    private function restore(string $dump)
    {
        $process = new Process([
            $this->option('mysqlcli'),
            '--host='.$this->connection()->getConfig('host'),
            '--password='.$this->connection()->getConfig('password'),
            '--port='.$this->connection()->getConfig('port'),
            '--user='.$this->connection()->getConfig('username'),
            '--database='.$this->database(),
            '--compress', // Deprecated as of MySQL 8.0.18
            '--compression-algorithms=zlib,zstd,uncompressed',
            '--default-character-set='.$this->connection()->getConfig('charset'),
        ], null, null, $file = fopen($dump, 'rb'), null);
        $process->mustRun();
        fclose($file);
        unlink($dump);
    }

    private function database(): string
    {
        return $this->connection()->getDatabaseName();
    }

    private function connection(): Connection
    {
        return DB::connection($this->argument('database'));
    }
}
