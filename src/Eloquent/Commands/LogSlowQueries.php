<?php

namespace Halaei\Helpers\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LogSlowQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:log-slow-queries {--connection=} {--sleep=2} {--once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log slow SQL queries';

    public function handle()
    {
        $dictionary = collect();
        while(true) {
            $list = DB::connection($this->option('connection'))->select('show full processlist');
            $slow = collect($list)
                ->where('Command', '!=', 'Sleep')
                ->whereNotNull('Info')
                ->where('Time', '>', 0)
                ->sortByDesc('Time')
                ->take(10);
            if ($slow->isNotEmpty()) {
                $dictionary = $dictionary->sortByDesc('total_time')->take(20)->reverse();
                $slow->each(function ($log) use ($dictionary) {
                    if (! $log->Info) {
                        return;
                    }
                    $stripped = $this->stripSql($log->Info);
                    $stats = $dictionary[$stripped] ?? null;
                    unset($dictionary[$stripped]);
                    $dictionary[$stripped] = [
                        'query' => $stripped,
                        'count' => ($stats['count'] ?? 0) + 1,
                        'total_time' => ($stats['total_time'] ?? 0) + $log->Time,
                        'last_time' => $log->Time,
                    ];
                });
                dump($dictionary->values()->all());
            }
            if ($this->option('once')) {
                return;
            }
            sleep($this->option('sleep'));
        }
    }

    private function stripSql($query)
    {
        $query = trim($query);
        $query = preg_replace('/"[^"]*"/', '?', $query);
        $query = preg_replace("/'[^']*'/", '?', $query);

        return preg_replace("/\b\d+\b/", '?', $query);
    }
}
