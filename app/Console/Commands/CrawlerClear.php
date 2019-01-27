<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\ProxyPool;
use Wuxiaobu\Proxy\Check;

class CrawlerClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tester = Check::getInstance();

        $proxies = ProxyPool::orderBy('last_checked_at')
            ->take(60)
            ->get();

        $proxies->each(function ($proxy) use ($tester) {
            $proxyIp = $proxy->protocol . '://' . $proxy->ip . ':' . $proxy->port;
            if ($speed = $tester->handle($proxyIp)) {
                $proxy->speed = $speed;
                $proxy->succeed_times = ++$proxy->succeed_times;
                $proxy->last_checked_at = Carbon::now();
            } else {
                $proxy->fail_times = ++$proxy->fail_times;
                $proxy->last_checked_at = Carbon::now();
            }
            $proxy->update();
        });
    }
}
